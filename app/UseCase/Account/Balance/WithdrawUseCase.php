<?php

declare(strict_types=1);

namespace App\UseCase\Account\Balance;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\WithdrawResultData;
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

    private AccountData $accountData;
    private WithdrawRequestData $withdrawRequestData;
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
    public function execute(WithdrawRequestData $withdrawRequestData): WithdrawResultData
    {
        try {
            $this->prepareExecute($withdrawRequestData);

            // Valida os dados da requisição
            $requestErrors = $withdrawRequestData->validate();
            if (!empty($requestErrors)) {
                return WithdrawResultData::validationError($requestErrors);
            }

            // Validação de saldo
            if (!$this->accountData->canWithdraw($withdrawRequestData->amount)) {
                return WithdrawResultData::error(
                    'INSUFFICIENT_BALANCE',
                    'Saldo insuficiente para realizar o saque.'
                );
            }

            // Gera ID da transação se não foi fornecido
            $transactionId = AccountWithdraw::generateTransactionId();

            // Processa baseado no tipo (imediato ou agendado)
            if ($this->withdrawRequestData->isScheduled()) {
                return $this->scheduleWithdraw($transactionId);
            } else {
                return $this->processImmediateWithdraw($transactionId);
            }

        } catch (\Throwable $e) {
            return WithdrawResultData::error(
                'PROCESSING_ERROR',
                'Erro interno ao processar o saque.',
                ['general' => [$e->getMessage()]]
            );
        }
    }

    private function prepareExecute(WithdrawRequestData $withdrawRequestData): void
    {
        $this->withdrawRequestData = $withdrawRequestData;
        // Temporary fix - will need to update AccountRepository to return AccountData
        $accountDto = $this->accountRepository->getAccountData($withdrawRequestData->accountId);
        $this->accountData = AccountData::fromModel($this->accountRepository->findById($withdrawRequestData->accountId));
    }

    /**
     * Processa saque imediato
     */
    private function processImmediateWithdraw(string $transactionId): WithdrawResultData
    {
        // Cria o registro do saque
        $accountWithdrawData = $this->createAccountWithdrawRecord($transactionId, false);

        // Marca como processando
        $this->accountWithdrawRepository->markAsProcessing((string) $accountWithdrawData->id);

        // Processa o débito na conta
        $debitSuccess = $this->accountRepository->debitAmount($this->accountData->id, $this->withdrawRequestData->amount);

        if (!$debitSuccess) {
            $this->accountWithdrawRepository->markAsFailed(
                (string) $accountWithdrawData->id, 
                'Erro ao debitar valor da conta.'
            );
            
            return WithdrawResultData::error(
                'DEBIT_ERROR',
                'Erro ao debitar valor da conta.'
            );
        }

        // Marca como concluído
        $this->accountWithdrawRepository->markAsCompleted((string) $accountWithdrawData->id);

        return WithdrawResultData::success([
            'account_id' => $this->accountData->id,
            'account_name' => $this->accountData->name,
            'amount' => $this->withdrawRequestData->amount,
            'current_balance' => (float) number_format($this->accountData->balance - $this->withdrawRequestData->amount, 2, '.', ''),
            'available_balance' => (float) number_format($this->accountData->availableBalance - $this->withdrawRequestData->amount, 2, '.', ''),
            'method' => $this->withdrawRequestData->method->value,
            'pix_key' => $this->withdrawRequestData->getPixKey(),
            'pix_type' => $this->withdrawRequestData->getPixType(),
            'type' => 'immediate',
            'withdraw_details' => $accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    private function createPixData(AccountWithdrawData $accountWithdrawData, WithdrawRequestData $request): AccountWithdrawPix
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
    private function scheduleWithdraw(string $transactionId): WithdrawResultData
    {
        // Cria o registro do saque agendado
        $accountWithdrawData = $this->createAccountWithdrawRecord($transactionId, true);

        #TODO Criar adapter para realizar a transferência - Suportado no mvp PIX(Service PixApiService por exemplo)

        return WithdrawResultData::scheduled([
            'account_id' => $this->accountData->id,
            'account_name' => $this->accountData->name,
            'amount' => $this->withdrawRequestData->amount,
            'current_balance' => (float) number_format($this->accountData->balance, 2, '.', ''),
            'available_balance' => (float) number_format($this->accountData->availableBalance - $this->withdrawRequestData->amount, 2, '.', ''),
            'method' => $this->withdrawRequestData->method->value,
            'scheduled_for' => $this->withdrawRequestData->schedule?->toISOString(),
            'pix_key' => $this->withdrawRequestData->getPixKey(),
            'pix_type' => $this->withdrawRequestData->getPixType(),
            'type' => 'scheduled',
            'withdraw_details' => $accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    private function createAccountWithdrawRecord(string $transactionId, bool $scheduled = false): AccountWithdrawData
    {
        return AccountWithdrawData::fromModel($this->accountWithdrawRepository->create([
            'id' => \Hyperf\Stringable\Str::uuid(),
            'account_id' => $this->accountData->id,
            'transaction_id' => $transactionId,
            'method' => $this->withdrawRequestData->method->value,
            'amount' => $this->withdrawRequestData->amount,
            'scheduled' => $scheduled,
            'scheduled_for' => $this->withdrawRequestData->schedule,
            'status' => $scheduled ? AccountWithdraw::STATUS_SCHEDULED : AccountWithdraw::STATUS_PENDING,
            'done' => false,
            'error' => false,
            'error_reason' => null,
            'meta' => $this->withdrawRequestData->metadata,
        ]));
    }

    /**
     * Verifica se a conta tem saldo suficiente
     */
    private function hasSufficientBalance(): bool
    {
        return (float) $this->accountData->balance >= $this->withdrawRequestData->amount;
    }
}
