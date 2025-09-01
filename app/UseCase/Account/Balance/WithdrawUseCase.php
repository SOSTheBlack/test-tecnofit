<?php

declare(strict_types=1);

namespace App\UseCase\Account\Balance;

use App\DataTransfer\Account\AccountData;
use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\DataTransfer\Account\Balance\WithdrawResultData;
use App\Model\AccountWithdraw;
use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Service\ScheduledWithdrawService;
use App\Service\WithdrawBusinessRules;
use App\Service\WithdrawNotificationService;
use App\Service\WithdrawService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Caso de uso responsável pela orquestração do processo de saque
 * 
 * Seguindo os princípios da Clean Architecture, esta classe atua apenas
 * como orquestrador, delegando a lógica de negócio para serviços especializados
 */
class WithdrawUseCase
{
    private readonly AccountRepositoryInterface $accountRepository;
    private readonly WithdrawService $withdrawService;
    private readonly WithdrawBusinessRules $businessRules;
    private readonly LoggerInterface $logger;

    public function __construct(
        ?AccountRepositoryInterface $accountRepository = null,
        ?AccountWithdrawRepositoryInterface $accountWithdrawRepository = null,
        ?ScheduledWithdrawService $scheduledWithdrawService = null,
        ?WithdrawBusinessRules $businessRules = null,
        ?WithdrawNotificationService $notificationService = null,
        ?LoggerInterface $logger = null
    ) {
        // Fallback para instâncias concretas quando dependências não são injetadas
        $this->accountRepository = $accountRepository ?? new AccountRepository();
        $accountWithdrawRepository = $accountWithdrawRepository ?? new AccountWithdrawRepository();
        $scheduledWithdrawService = $scheduledWithdrawService ?? new ScheduledWithdrawService();
        
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
            $scheduledWithdrawService,
            $this->logger
        );
    }

    /**
     * Executa o saque (imediato ou agendado)
     * 
     * Atua como orquestrador, delegando a lógica de negócio para serviços especializados
     */
    public function execute(WithdrawRequestData $withdrawRequestData): WithdrawResultData
    {
        try {
            // 1. Busca e valida existência da conta
            $account = $this->accountRepository->findById($withdrawRequestData->accountId);
            if (!$account) {
                return WithdrawResultData::processingError('Conta não encontrada para o ID fornecido.');
            }
            
            $accountData = AccountData::fromModel($account);

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

            // 4. Criação do registro de saque
            $transactionId = AccountWithdraw::generateTransactionId();
            $accountWithdrawData = $this->withdrawService->createWithdrawRecord(
                $accountData,
                $withdrawRequestData,
                $transactionId,
                $withdrawRequestData->isScheduled()
            );

            // 5. Roteamento baseado no tipo de saque
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
}
