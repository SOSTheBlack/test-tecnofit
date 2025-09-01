<?php

declare(strict_types=1);

namespace App\Service\Withdraw;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\WithdrawResultData;
use App\Enum\PixKeyTypeEnum;
use App\Enum\WithdrawStatusEnum;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Serviço de domínio responsável pelas operações centrais de saque
 * 
 * Coordena as operações relacionadas a saques, mantendo a lógica de negócio
 * separada da orquestração do caso de uso
 */
class WithdrawService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccountWithdrawRepositoryInterface $accountWithdrawRepository,
        private readonly WithdrawBusinessRules $businessRules,
        private readonly WithdrawNotificationService $notificationService,
        private readonly ScheduledWithdrawService $scheduledWithdrawService
    ) {
    }

    /**
     * Processa um saque imediato
     */
    public function processImmediateWithdraw(
        AccountData $accountData,
        WithdrawRequestData $withdrawRequestData,
        AccountWithdrawData $accountWithdrawData,
        string $transactionId
    ): WithdrawResultData {
        // Processa o débito na conta de forma atômica
        $debitSuccess = $this->accountRepository->debitAmount($accountData->id, $withdrawRequestData->amount);

        if (!$debitSuccess) {
            return $this->handleDebitFailure($accountWithdrawData);
        }

        // Marca como concluído após débito bem-sucedido
        $this->accountWithdrawRepository->markAsCompleted((string) $accountWithdrawData->id);

        // Agenda notificação por email (se aplicável)
        $this->notificationService->scheduleEmailNotification($accountWithdrawData->id, $withdrawRequestData);

        // Calcula novos saldos
        $newBalances = $this->businessRules->calculateNewBalanceAfterWithdraw($accountData, $withdrawRequestData->amount);

        return WithdrawResultData::success([
            'account_id' => $accountData->id,
            'account_name' => $accountData->name,
            'amount' => $withdrawRequestData->amount,
            'current_balance' => $newBalances['current_balance'],
            'available_balance' => $newBalances['available_balance'],
            'method' => $withdrawRequestData->method->value,
            'pix_key' => $withdrawRequestData->getPixKey(),
            'pix_type' => $withdrawRequestData->getPixType(),
            'type' => 'immediate',
            'withdraw_details' => $accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    /**
     * Processa um saque agendado
     */
    public function processScheduledWithdraw(
        AccountData $accountData,
        WithdrawRequestData $withdrawRequestData,
        AccountWithdrawData $accountWithdrawData,
        string $transactionId
    ): WithdrawResultData {
        // Calcula saldo disponível após reserva do valor agendado
        $newBalances = $this->businessRules->calculateNewBalanceAfterWithdraw($accountData, $withdrawRequestData->amount);

        // Agenda job assíncrono para processar saque na data correta
        $jobScheduled = $this->scheduledWithdrawService->scheduleWithdrawJob(
            $accountWithdrawData->id,
            $withdrawRequestData->schedule ?? throw new \InvalidArgumentException('Schedule date is required for scheduled withdraw')
        );

        if (!$jobScheduled) {
            return $this->handleSchedulingFailure($accountWithdrawData);
        }

        return WithdrawResultData::scheduled([
            'account_id' => $accountData->id,
            'account_name' => $accountData->name,
            'amount' => $withdrawRequestData->amount,
            'current_balance' => (float) number_format($accountData->balance, 2, '.', ''),
            'available_balance' => $newBalances['available_balance'],
            'method' => $withdrawRequestData->method->value,
            'scheduled_for' => $withdrawRequestData->schedule->toISOString(),
            'pix_key' => $withdrawRequestData->getPixKey(),
            'pix_type' => $withdrawRequestData->getPixType(),
            'type' => 'scheduled',
            'withdraw_details' => $accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    /**
     * Cria ou busca registro do saque no banco
     */
    public function createWithdrawRecord(
        AccountData $accountData,
        WithdrawRequestData $withdrawRequestData,
        string $transactionId,
        bool $scheduled = false
    ): AccountWithdrawData {
        $status = $scheduled ? WithdrawStatusEnum::PENDING->value : WithdrawStatusEnum::NEW->value;

        if (!is_null($withdrawRequestData->id)) {
            $withdraw = $this->accountWithdrawRepository->findWithdrawById($withdrawRequestData->id);
            if ($withdraw === null) {
                throw new \InvalidArgumentException('Withdraw not found');
            }
            return $withdraw;
        }

        $accountWithdrawData = $this->accountWithdrawRepository->createWithdraw([
            'account_id' => $accountData->id,
            'transaction_id' => $transactionId,
            'method' => $withdrawRequestData->method->value,
            'amount' => $withdrawRequestData->amount,
            'scheduled' => $scheduled,
            'scheduled_for' => $withdrawRequestData->schedule,
            'status' => $status,
            'meta' => $withdrawRequestData->metadata
        ]);

        // Cria dados PIX se necessário
        if ($this->shouldCreatePixData($withdrawRequestData)) {
            $pixKey = $withdrawRequestData->getPixKey();
            $pixType = $withdrawRequestData->getPixType();
            
            if ($pixKey !== null && $pixType !== null) {
                $this->accountWithdrawRepository->createPixData(
                    $accountWithdrawData->id,
                    $pixKey,
                    $pixType
                );
            }
        }

        return $accountWithdrawData;
    }

    /**
     * Trata falha no débito da conta
     */
    private function handleDebitFailure(AccountWithdrawData $accountWithdrawData): WithdrawResultData
    {
        $this->accountWithdrawRepository->markAsFailed(
            $accountWithdrawData->id,
            'Erro ao debitar valor da conta.'
        );
        
        return WithdrawResultData::debitError();
    }

    /**
     * Trata falha no agendamento do saque
     */
    private function handleSchedulingFailure(AccountWithdrawData $accountWithdrawData): WithdrawResultData
    {
        $this->accountWithdrawRepository->markAsFailed(
            $accountWithdrawData->id,
            'Erro ao agendar processamento automático do saque'
        );
        
        return WithdrawResultData::processingError(
            'Erro ao agendar o saque. Tente novamente.'
        );
    }

    /**
     * Verifica se deve criar dados PIX
     */
    private function shouldCreatePixData(WithdrawRequestData $withdrawRequestData): bool
    {
        return $withdrawRequestData->isPixMethod() 
            && $withdrawRequestData->getPixType() === PixKeyTypeEnum::EMAIL->value;
    }
}