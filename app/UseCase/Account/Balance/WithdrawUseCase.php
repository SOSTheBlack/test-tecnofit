<?php

declare(strict_types=1);

namespace App\UseCase\Account\Balance;

use App\DTO\Account\Balance\AccountDataDTO;
use App\DTO\Account\Balance\AccountWithdrawDTO;
use App\DTO\Account\Balance\WithdrawRequestDTO;
use App\DTO\Account\Balance\WithdrawResultDTO;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use Hyperf\DbConnection\Db;
use Throwable;

class WithdrawUseCase
{

    private AccountDataDTO $accountData;
    private WithdrawRequestDTO $withdrawRequestDTO;
    private AccountRepositoryInterface $accountRepository;
    private AccountWithdrawRepositoryInterface $accountWithdrawRepository;


    public function __construct()
    {
        $this->accountRepository = new AccountRepository();
        $this->accountWithdrawRepository = new AccountWithdrawRepository();
    }

    /**
     * Executa o saque (imediato ou agendado)
     */
    public function execute(WithdrawRequestDTO $withdrawRequestDTO): WithdrawResultDTO
    {
        $this->prepareExecute($withdrawRequestDTO);

        // Validação inicial do DTO
        $requestErrors = $withdrawRequestDTO->validate();
        if (!empty($requestErrors)) {
            return WithdrawResultDTO::validationError($requestErrors);
        }

        // Validar se tem saldo suficiente
        if (!$this->hasSufficientBalance()) {
            return WithdrawResultDTO::error(
                'INSUFFICIENT_BALANCE',
                sprintf(
                    'Saldo insuficiente. Saldo disponível: R$ %.2f, Valor solicitado: R$ %.2f',
                    $this->accountData->availableBalance,
                    $this->withdrawRequestDTO->amount
                )
            );
        }

        try {
            return Db::transaction(function () {
                $transactionId = AccountWithdraw::generateTransactionId();

                if ($this->withdrawRequestDTO->isScheduled()) {
                    return $this->scheduleWithdraw($transactionId);
                }

                return $this->processImmediateWithdraw($transactionId);
            });
        } catch (Throwable $e) {
            return WithdrawResultDTO::error(
                'PROCESSING_ERROR',
                'Erro interno ao processar o saque.',
                ['general' => [$e->getMessage()]]
            );
        }
    }

    private function prepareExecute(WithdrawRequestDTO $withdrawRequestDTO): void
    {
        $this->withdrawRequestDTO = $withdrawRequestDTO;
        $this->accountData = $this->accountRepository->getAccountData($withdrawRequestDTO->accountId);;
    }

        /**
     * Processa saque imediato
     */
    private function processImmediateWithdraw(string $transactionId): WithdrawResultDTO
    {
        // Cria o registro do saque
        $accountWithdrawDTO = $this->createAccountWithdrawRecord($transactionId, false);

        // Marca como processando
        $this->accountWithdrawRepository->markAsProcessing((string) $accountWithdrawDTO->id);

        // Processa o débito na conta
        $debitSuccess = $this->accountRepository->debitAmount($this->accountData->id, $this->withdrawRequestDTO->amount);

        if (!$debitSuccess) {
            $this->accountWithdrawRepository->markAsFailed(
                (string) $accountWithdrawDTO->id, 
                'Erro ao debitar valor da conta.'
            );
            
            return WithdrawResultDTO::error(
                'DEBIT_ERROR',
                'Erro ao debitar valor da conta.'
            );
        }

        // Marca como concluído
        $this->accountWithdrawRepository->markAsCompleted((string) $accountWithdrawDTO->id);

        return WithdrawResultDTO::success([
            'account_id' => $this->accountData->id,
            'account_name' => $this->accountData->name,
            'amount' => $this->withdrawRequestDTO->amount,
            'current_balance' => (float) number_format($this->accountData->balance - $this->withdrawRequestDTO->amount, 2, '.', ''),
            'available_balance' => (float) number_format($this->accountData->availableBalance - $this->withdrawRequestDTO->amount, 2, '.', ''),
            'method' => $this->withdrawRequestDTO->method->value,
            'pix_key' => $this->withdrawRequestDTO->getPixKey(),
            'pix_type' => $this->withdrawRequestDTO->getPixType(),
            'type' => 'immediate',
            'withdraw_details' => $accountWithdrawDTO->toSummary(),
        ], $transactionId);
    }

    private function createPixData(AccountWithdrawDTO $accountWithdrawData, WithdrawRequestDTO $request): AccountWithdrawPix
    {
        return AccountWithdrawPix::create([
            'id' => \Hyperf\Stringable\Str::uuid(),
            'account_withdraw_id' => $accountWithdrawData->id,
            'type' => $request->getPixType(),
            'key' => $request->getPixKey(),
        ]);
    }

    /**
     * Agenda o saque para data futura
     */
    private function scheduleWithdraw(string $transactionId): WithdrawResultDTO
    {
        // Cria o registro do saque agendado
        $accountWithdrawDTO = $this->createAccountWithdrawRecord($transactionId, true);

        #TODO Criar adapter para realizar a transferência - Suportado no mvp PIX(Service PixApiService por exemplo)

        return WithdrawResultDTO::scheduled([
            'account_id' => $this->accountData->id,
            'account_name' => $this->accountData->name,
            'amount' => $this->withdrawRequestDTO->amount,
            'current_balance' => (float) number_format($this->accountData->balance, 2, '.', ''),
            'available_balance' => (float) number_format($this->accountData->availableBalance - $this->withdrawRequestDTO->amount, 2, '.', ''),
            'method' => $this->withdrawRequestDTO->method->value,
            'scheduled_for' => $this->withdrawRequestDTO->schedule?->toISOString(),
            'pix_key' => $this->withdrawRequestDTO->getPixKey(),
            'pix_type' => $this->withdrawRequestDTO->getPixType(),
            'type' => 'scheduled',
            'withdraw_details' => $accountWithdrawDTO->toSummary(),
        ], $transactionId);
    }

    private function createAccountWithdrawRecord(string $transactionId, bool $scheduled = false): AccountWithdrawDTO
    {
        return AccountWithdrawDTO::fromModel($this->accountWithdrawRepository->create([
            'id' => \Hyperf\Stringable\Str::uuid(),
            'account_id' => $this->accountData->id,
            'transaction_id' => $transactionId,
            'method' => $this->withdrawRequestDTO->method->value,
            'amount' => $this->withdrawRequestDTO->amount,
            'scheduled' => $scheduled,
            'scheduled_for' => $this->withdrawRequestDTO->schedule,
            'status' => $scheduled ? AccountWithdraw::STATUS_SCHEDULED : AccountWithdraw::STATUS_PENDING,
            'done' => false,
            'error' => false,
            'error_reason' => null,
            'meta' => $this->withdrawRequestDTO->metadata,
        ]));
    }

    /**
     * Verifica se a conta tem saldo suficiente
     */
    private function hasSufficientBalance(): bool
    {
        return (float) $this->accountData->balance >= $this->withdrawRequestDTO->amount;
    }
}
