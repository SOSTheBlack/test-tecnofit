<?php

declare(strict_types=1);

namespace App\UseCase\Account\Balance;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\WithdrawResultData;
use App\Model\AccountWithdraw;
use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Service\ScheduledWithdrawService;
use Throwable;

class WithdrawUseCase
{
    /**
     * Dependências imutáveis injetadas via construtor para garantir
     * thread-safety e testabilidade
     */
    private readonly AccountRepositoryInterface $accountRepository;
    private readonly AccountWithdrawRepositoryInterface $accountWithdrawRepository;
    private readonly ScheduledWithdrawService $scheduledWithdrawService;

    public function __construct(
        ?AccountRepositoryInterface $accountRepository = null,
        ?AccountWithdrawRepositoryInterface $accountWithdrawRepository = null,
        ?ScheduledWithdrawService $scheduledWithdrawService = null
    ) {
        // Fallback para instâncias concretas quando dependências não são injetadas
        // Em produção, recomenda-se usar container de DI do Hyperf
        $this->accountRepository = $accountRepository ?? new AccountRepository();
        $this->accountWithdrawRepository = $accountWithdrawRepository ?? new AccountWithdrawRepository();
        $this->scheduledWithdrawService = $scheduledWithdrawService ?? new ScheduledWithdrawService();
    }

    /**
     * Executa o saque (imediato ou agendado)
     * 
     * Mantém todas as variáveis do fluxo como locais para garantir thread-safety
     * e evitar efeitos colaterais entre requisições concorrentes
     */
    public function execute(WithdrawRequestData $withdrawRequestData): WithdrawResultData
    {
        try {
            // Busca dados da conta - validação de existência implícita
            $account = $this->accountRepository->findById($withdrawRequestData->accountId);
            if (!$account) {
                return WithdrawResultData::processingError('Conta não encontrada para o ID fornecido.');
            }
            
            $accountData = AccountData::fromModel($account);

            // Validação dos dados da requisição
            $requestErrors = $withdrawRequestData->validate();
            if (!empty($requestErrors)) {
                return WithdrawResultData::validationError($requestErrors);
            }

            // Validação de saldo disponível
            if (!$accountData->canWithdraw($withdrawRequestData->amount)) {
                return WithdrawResultData::insufficientBalance();
            }

            // Gera ID único da transação
            $transactionId = AccountWithdraw::generateTransactionId();
            
            // Cria registro do saque no banco
            $accountWithdrawData = $this->createOrFindAccountWithdraw(
                $accountData,
                $withdrawRequestData,
                $transactionId,
                $withdrawRequestData->isScheduled()
            );

            // Roteamento baseado no tipo de saque
            if ($withdrawRequestData->isScheduled()) {
                return $this->scheduleWithdraw($accountData, $withdrawRequestData, $accountWithdrawData, $transactionId);
            }
            
            return $this->processImmediateWithdraw($accountData, $withdrawRequestData, $accountWithdrawData, $transactionId);
            
        } catch (\Throwable $e) {
            // Log do erro para auditoria e debugging
            error_log('Erro ao processar saque: ' . print_r($e, true));
            return WithdrawResultData::processingError(
                'Erro interno ao processar o saque.',
                ['general' => [$e->getMessage()]]
            );
        }
    }

    /**
     * Processa saque imediato
     * 
     * Recebe todos os dados necessários como parâmetros, mantendo o método
     * puro e sem efeitos colaterais
     */
    private function processImmediateWithdraw(
        AccountData $accountData,
        WithdrawRequestData $withdrawRequestData,
        AccountWithdrawData $accountWithdrawData,
        string $transactionId
    ): WithdrawResultData {
        // Processa o débito na conta de forma atômica
        $debitSuccess = $this->accountRepository->debitAmount($accountData->id, $withdrawRequestData->amount);

        if (!$debitSuccess) {
            // Marca como falha em caso de erro no débito
            $this->accountWithdrawRepository->markAsFailed(
                $accountWithdrawData->id,
                'Erro ao debitar valor da conta.'
            );
            
            return WithdrawResultData::debitError();
            return WithdrawResultData::debitError();
        }

        // Marca como concluído após débito bem-sucedido
        $this->accountWithdrawRepository->markAsCompleted((string) $accountWithdrawData->id);

        // Calcula saldos atualizados para resposta
        $newCurrentBalance = $accountData->balance - $withdrawRequestData->amount;
        $newAvailableBalance = $accountData->availableBalance - $withdrawRequestData->amount;

        return WithdrawResultData::success([
            'account_id' => $accountData->id,
            'account_name' => $accountData->name,
            'amount' => $withdrawRequestData->amount,
            'current_balance' => (float) number_format($newCurrentBalance, 2, '.', ''),
            'available_balance' => (float) number_format($newAvailableBalance, 2, '.', ''),
            'method' => $withdrawRequestData->method->value,
            'pix_key' => $withdrawRequestData->getPixKey(),
            'pix_type' => $withdrawRequestData->getPixType(),
            'type' => 'immediate',
            'withdraw_details' => $accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    /**
     * Agenda o saque para data futura
     * 
     * Mantém dados locais e permite processamento assíncrono posterior
     */
    private function scheduleWithdraw(
        AccountData $accountData,
        WithdrawRequestData $withdrawRequestData,
        AccountWithdrawData $accountWithdrawData,
        string $transactionId
    ): WithdrawResultData {
        // Calcula saldo disponível após reserva do valor agendado
        $newAvailableBalance = $accountData->availableBalance - $withdrawRequestData->amount;

        // Agenda job assíncrono para processar saque na data correta
        $jobScheduled = $this->scheduledWithdrawService->scheduleWithdrawJob(
            $accountWithdrawData->id,
            $withdrawRequestData->schedule
        );

        if (!$jobScheduled) {
            // Se falhar ao agendar job, marca o saque como falha
            $this->accountWithdrawRepository->markAsFailed(
                $accountWithdrawData->id,
                'Erro ao agendar processamento automático do saque'
            );
            
            return WithdrawResultData::processingError(
                'Erro ao agendar o saque. Tente novamente.'
            );
        }

        return WithdrawResultData::scheduled([
            'account_id' => $accountData->id,
            'account_name' => $accountData->name,
            'amount' => $withdrawRequestData->amount,
            'current_balance' => (float) number_format($accountData->balance, 2, '.', ''),
            'available_balance' => (float) number_format($newAvailableBalance, 2, '.', ''),
            'method' => $withdrawRequestData->method->value,
            'scheduled_for' => $withdrawRequestData->schedule?->toISOString(),
            'pix_key' => $withdrawRequestData->getPixKey(),
            'pix_type' => $withdrawRequestData->getPixType(),
            'type' => 'scheduled',
            'withdraw_details' => $accountWithdrawData->toSummary(),
        ], $transactionId);
    }

    /**
     * Cria ou busca registro do saque no banco
     */
    private function createOrFindAccountWithdraw(
        AccountData $accountData,
        WithdrawRequestData $withdrawRequestData,
        string $transactionId,
        bool $scheduled = false
    ): AccountWithdrawData {
        $status = $scheduled ? AccountWithdraw::STATUS_PENDING : AccountWithdraw::STATUS_NEW;

        if (!is_null($withdrawRequestData->id)) {
            return AccountWithdrawData::fromModel($this->accountWithdrawRepository->findById($withdrawRequestData->id));
        }

        return AccountWithdrawData::fromModel($this->accountWithdrawRepository->create([
            'account_id' => $accountData->id,
            'transaction_id' => $transactionId,
            'method' => $withdrawRequestData->method->value,
            'amount' => $withdrawRequestData->amount,
            'scheduled' => $scheduled,
            'scheduled_for' => $withdrawRequestData->schedule,
            'status' => $status,
            'meta' => $withdrawRequestData->metadata,
        ]));
    }
}
