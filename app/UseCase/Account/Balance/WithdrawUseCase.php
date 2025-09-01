<?php

declare(strict_types=1);

namespace App\UseCase\Account\Balance;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\WithdrawResultData;
use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Service\Email\EmailService;
use App\Service\Transaction\TransactionIdService;
use App\Service\Withdraw\ScheduledWithdrawService;
use App\Service\Withdraw\WithdrawBusinessRules;
use App\Service\Withdraw\WithdrawNotificationService;
use App\Service\Withdraw\WithdrawService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Caso de uso responsável pela orquestração do processo de saque
 * 
 * Seguindo os princípios da Clean Architecture, esta classe atua apenas
 * como orquestrador, delegando a lógica de negócio para serviços especializados.
 * 
 * Não utiliza models diretamente, apenas DTOs e serviços especializados.
 */
class WithdrawUseCase
{
    private readonly AccountRepositoryInterface $accountRepository;
    private readonly WithdrawService $withdrawService;
    private readonly WithdrawBusinessRules $businessRules;
    private readonly TransactionIdService $transactionIdService;
    private readonly LoggerInterface $logger;

    public function __construct(
        ?AccountRepositoryInterface $accountRepository = null,
        ?AccountWithdrawRepositoryInterface $accountWithdrawRepository = null,
        ?ScheduledWithdrawService $scheduledWithdrawService = null,
        ?WithdrawBusinessRules $businessRules = null,
        ?WithdrawNotificationService $notificationService = null,
        ?TransactionIdService $transactionIdService = null,
        ?LoggerInterface $logger = null
    ) {
        // Fallback para instâncias concretas quando dependências não são injetadas
        $this->accountRepository = $accountRepository ?? new AccountRepository();
        $accountWithdrawRepository = $accountWithdrawRepository ?? new AccountWithdrawRepository();
        $scheduledWithdrawService = $scheduledWithdrawService ?? new ScheduledWithdrawService();
        
        // Serviço de geração de IDs de transação
        $this->transactionIdService = $transactionIdService ?? new TransactionIdService($accountWithdrawRepository);
        
        // Logger para auditoria de saques
        $loggerFactory = ApplicationContext::getContainer()->get(LoggerFactory::class);
        $this->logger = $logger ?? $loggerFactory->get('withdraw', 'default');
        
        // Serviços especializados
        $this->businessRules = $businessRules ?? new WithdrawBusinessRules();
        $notificationService = $notificationService ?? new WithdrawNotificationService($this->logger);
        
        $this->withdrawService = new WithdrawService(
            $this->accountRepository,
            $accountWithdrawRepository,
            $this->businessRules,
            $notificationService,
            $scheduledWithdrawService
        );
    }

    /**
     * Executa o saque (imediato ou agendado)
     * 
     * Atua como orquestrador, delegando a lógica de negócio para serviços especializados.
     * Segue o padrão de Clean Architecture, trabalhando apenas com DTOs e não acessando
     * models diretamente.
     * 
     * @param WithdrawRequestData $withdrawRequestData Dados da requisição de saque
     * @return WithdrawResultData Resultado da operação de saque
     */
    public function execute(WithdrawRequestData $withdrawRequestData): WithdrawResultData
    {
        try {
            // 1. Busca e valida existência da conta através do repositório
            $accountData = $this->accountRepository->findById($withdrawRequestData->accountId);
            if (!$accountData) {
                return WithdrawResultData::processingError('Conta não encontrada para o ID fornecido.');
            }

            // 2. Validação dos dados da requisição
            $requestErrors = $withdrawRequestData->validate();
            if (!empty($requestErrors)) {
                return WithdrawResultData::validationError($requestErrors);
            }

            // 3. Aplicação das regras de negócio
            $businessErrors = $this->businessRules->validateWithdrawRequest($accountData, $withdrawRequestData);
            if (!empty($businessErrors)) {
                return WithdrawResultData::validationError($businessErrors);
            }

            // 4. Geração de ID de transação através do serviço especializado
            $transactionId = $this->generateTransactionIdByMethod($withdrawRequestData);
            
            // 5. Criação do registro de saque
            $accountWithdrawData = $this->withdrawService->createWithdrawRecord(
                $accountData,
                $withdrawRequestData,
                $transactionId,
                $withdrawRequestData->isScheduled()
            );

            // 6. Roteamento baseado no tipo de saque
            if ($withdrawRequestData->isScheduled()) {
                return $this->withdrawService->processScheduledWithdraw(
                    $accountData,
                    $withdrawRequestData,
                    $accountWithdrawData,
                    $transactionId
                );
            }
            
            return $this->withdrawService->processImmediateWithdraw(
                $accountData,
                $withdrawRequestData,
                $accountWithdrawData,
                $transactionId
            );
            
        } catch (\Throwable $e) {
            // Log do erro para auditoria e debugging
            $this->logger->error('Erro ao processar saque', [
                'error' => $e->getMessage(),
                'exception' => $e,
                'account_id' => $withdrawRequestData->accountId ?? 'unknown'
            ]);
            
            return WithdrawResultData::processingError(
                'Erro interno ao processar o saque.',
                ['general' => [$e->getMessage()]]
            );
        }
    }

    /**
     * Gera ID de transação baseado no método de saque
     * 
     * @param WithdrawRequestData $withdrawRequestData Dados da requisição
     * @return string ID de transação gerado
     */
    private function generateTransactionIdByMethod(WithdrawRequestData $withdrawRequestData): string
    {
        return match ($withdrawRequestData->method->value) {
            'PIX' => $this->transactionIdService->generatePixTransactionId(),
            'BANK_TRANSFER', 'TED' => $this->transactionIdService->generateBankTransferTransactionId(),
            default => $this->transactionIdService->generateTransactionId(),
        };
    }
}
