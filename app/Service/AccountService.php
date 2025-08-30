<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Account\Balance\AccountDataDTO;
use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\DTO\Account\Balance\WithdrawResultDTO;
use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Repository\Contract\AccountRepositoryInterface;
use Hyperf\DbConnection\Db;
use Throwable;

class AccountService
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function findAccountById(string $accountId): ?Account
    {
        return $this->accountRepository->findById($accountId);
    }

    public function getAccountData(string $accountId): ?AccountDataDTO
    {
        $account = $this->findAccountById($accountId);
        
        return $account ? AccountDataDTO::fromModel($account) : null;
    }

    private function executeWithdraw(Account $account, AccountDataDTO $accountData, WithdrawRequestDTO $request): WithdrawResultDTO
    {
        // Verifica saldo suficiente
        if (!$accountData->canWithdraw($request->amount)) {
            return WithdrawResultDTO::error(
                'INSUFFICIENT_BALANCE',
                sprintf(
                    'Saldo insuficiente. Saldo disponível: R$ %.2f, Valor solicitado: R$ %.2f',
                    $accountData->availableBalance,
                    $request->amount
                )
            );
        }

        try {
            return Db::transaction(function () use ($account, $request) {
                // Gera ID único da transação
                $transactionId = AccountWithdraw::generateTransactionId();

                if ($request->isScheduled()) {
                    return $this->scheduleWithdraw($account, $request, $transactionId);
                }

                return $this->processImmediateWithdraw($account, $request, $transactionId);
            });
        } catch (Throwable $e) {
            return WithdrawResultDTO::error(
                'PROCESSING_ERROR',
                'Erro interno ao processar o saque: ' . $e->getMessage()
            );
        }
    }

    private function processImmediateWithdraw(Account $account, WithdrawRequestDTO $request, string $transactionId): WithdrawResultDTO
    {
        // Cria o registro do saque
        $withdraw = $this->createWithdrawRecord($account, $request, $transactionId, false);

        // Marca como processando
        $withdraw->markAsProcessing();

        // Processa o débito na conta
        $debitSuccess = $account->debit($request->amount);
        
        if (!$debitSuccess) {
            $withdraw->markAsFailed('Erro ao debitar valor da conta.');
            return WithdrawResultDTO::error(
                'DEBIT_FAILED',
                'Erro ao processar o débito na conta.'
            );
        }

        // Cria dados PIX se necessário
        if ($request->isPixMethod() && $request->hasPixData()) {
            $this->createPixData($withdraw, $request);
        }

        // Marca como completado
        $withdraw->markAsCompleted([
            'processed_balance' => $account->balance,
            'processing_time' => \Carbon\Carbon::now()->toISOString(),
        ]);

        return WithdrawResultDTO::success([
            'account_id' => $account->id,
            'account_name' => $account->name,
            'amount' => $request->amount,
            'new_balance' => $account->balance,
            'available_balance' => $account->getAvailableBalance(),
            'method' => $request->method->value,
            'pix_key' => $request->getPixKey(),
            'pix_type' => $request->getPixType(),
            'type' => 'immediate',
        ], $transactionId);
    }

    private function scheduleWithdraw(Account $account, WithdrawRequestDTO $request, string $transactionId): WithdrawResultDTO
    {
        // Cria o registro do saque agendado
        $withdraw = $this->createWithdrawRecord($account, $request, $transactionId, true);

        // Cria dados PIX se necessário
        if ($request->isPixMethod() && $request->hasPixData()) {
            $this->createPixData($withdraw, $request);
        }

        return WithdrawResultDTO::scheduled([
            'account_id' => $account->id,
            'account_name' => $account->name,
            'amount' => $request->amount,
            'current_balance' => $account->balance,
            'available_balance' => $account->getAvailableBalance(),
            'method' => $request->method->value,
            'scheduled_for' => $request->schedule->toISOString(),
            'pix_key' => $request->getPixKey(),
            'pix_type' => $request->getPixType(),
            'type' => 'scheduled',
        ], $transactionId);
    }

    private function createWithdrawRecord(Account $account, WithdrawRequestDTO $request, string $transactionId, bool $scheduled): AccountWithdraw
    {
        return AccountWithdraw::create([
            'id' => \Hyperf\Stringable\Str::uuid(),
            'account_id' => $account->id,
            'transaction_id' => $transactionId,
            'method' => $request->method->value,
            'amount' => $request->amount,
            'scheduled' => $scheduled,
            'scheduled_for' => $request->schedule,
            'status' => $scheduled ? AccountWithdraw::STATUS_SCHEDULED : AccountWithdraw::STATUS_PENDING,
            'meta' => $request->metadata,
        ]);
    }

    private function createPixData(AccountWithdraw $withdraw, WithdrawRequestDTO $request): AccountWithdrawPix
    {
        return AccountWithdrawPix::create([
            'id' => \Hyperf\Stringable\Str::uuid(),
            'account_withdraw_id' => $withdraw->id,
            'type' => $request->getPixType(),
            'key' => $request->getPixKey(),
        ]);
    }

    // Métodos de gestão de conta mantidos com melhorias
    public function createAccount(array $data): Account
    {
        return $this->accountRepository->create($data);
    }

    public function updateAccount(string $accountId, array $data): bool
    {
        return $this->accountRepository->update($accountId, $data);
    }

    public function deleteAccount(string $accountId): bool
    {
        return $this->accountRepository->delete($accountId);
    }

    public function getAllAccounts(): array
    {
        return $this->accountRepository->findAll();
    }

    public function searchAccountsByName(string $name): array
    {
        return $this->accountRepository->findByName($name);
    }

    // Novos métodos com DTOs
    public function getAccountBalance(string $accountId): ?float
    {
        $accountData = $this->getAccountData($accountId);
        return $accountData?->balance;
    }

    public function getAvailableBalance(string $accountId): ?float
    {
        $accountData = $this->getAccountData($accountId);
        return $accountData?->availableBalance;
    }

    public function hasSufficientBalance(string $accountId, float $amount): bool
    {
        $accountData = $this->getAccountData($accountId);
        return $accountData?->canWithdraw($amount) ?? false;
    }
}
