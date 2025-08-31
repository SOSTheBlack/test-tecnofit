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
    private AccountWithdrawData $accountWithdrawData;


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
            $this->withdrawRequestData = $withdrawRequestData;
            $this->accountData = AccountData::fromModel($this->accountRepository->findById($this->withdrawRequestData->accountId));

            $transactionId = AccountWithdraw::generateTransactionId();
            $this->accountWithdrawData = $this->createAccountWithdrawRecord($transactionId, $this->withdrawRequestData->isScheduled());
            if ($this->withdrawRequestData->isPixMethod()) {
                $this->createPixData($this->accountWithdrawData, $this->withdrawRequestData);
            }

            // Valida os dados da requisição
            $requestErrors = $this->withdrawRequestData->validate();
            if (!empty($requestErrors)) {
                return WithdrawResultData::validationError($requestErrors);
            }

            // Validação de saldo
            if (!$this->accountData->canWithdraw($this->withdrawRequestData->amount)) {
                return WithdrawResultData::insufficientBalance();
            }

            // Marca como processando
            $this->accountWithdrawRepository->markAsProcessing((string) $this->accountWithdrawData->id);

            // Processa baseado no tipo (imediato ou agendado)
            if ($this->withdrawRequestData->isScheduled()) {
                return $this->scheduleWithdraw($transactionId);
            }
            
            return $this->processImmediateWithdraw($transactionId);
        } catch (\Throwable $e) {
            error_log('Erro ao processar saque: ' . print_r($e, true));
            return WithdrawResultData::processingError(
                'Erro interno ao processar o saque.',
                ['general' => [$e->getMessage()]]
            );
        }
    }

    /**
     * Processa saque imediato
     */
    private function processImmediateWithdraw(string $transactionId): WithdrawResultData
    {
        // Processa o débito na conta
        $debitSuccess = $this->accountRepository->debitAmount($this->accountData->id, $this->withdrawRequestData->amount);

        if (!$debitSuccess) {
            $this->accountWithdrawRepository->markAsFailed(
                $this->accountWithdrawData->id,
                'Erro ao debitar valor da conta.'
            );
            
            return WithdrawResultData::debitError();
        }

        // Marca como concluído
        $this->accountWithdrawRepository->markAsCompleted((string) $this->accountWithdrawData->id);

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
            'withdraw_details' => $this->accountWithdrawData->toSummary(),
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
            'withdraw_details' => $this->accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    private function createAccountWithdrawRecord(string $transactionId, bool $scheduled = false): AccountWithdrawData
    {
        return AccountWithdrawData::fromModel($this->accountWithdrawRepository->create([
            'account_id' => $this->accountData->id,
            'transaction_id' => $transactionId,
            'method' => $this->withdrawRequestData->method->value,
            'amount' => $this->withdrawRequestData->amount,
            'scheduled' => $scheduled,
            'scheduled_for' => $this->withdrawRequestData->schedule,
            'status' => AccountWithdraw::STATUS_NEW,
            'meta' => $this->withdrawRequestData->metadata,
        ]));
    }
}
