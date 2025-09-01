<?php

declare(strict_types=1);

namespace App\Job\Account\Balance;

use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawPixRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\AccountWithdrawPixRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Service\Email\EmailService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Job assíncrono para envio de notificações de saque
 *
 * Responsável por enviar notificações por email quando um saque é processado com sucesso.
 * Segue o padrão de não utilizar models diretamente, apenas DTOs e repositórios.
 */
class SendWithdrawNotificationJob extends Job
{
    private string $withdrawId;
    private LoggerInterface $logger;
    private AccountWithdrawRepositoryInterface $withdrawRepository;
    private AccountWithdrawPixRepositoryInterface $pixRepository;
    private AccountRepositoryInterface $accountRepository;

    public function __construct(string $withdrawId)
    {
        $this->withdrawId = $withdrawId;

        $container = ApplicationContext::getContainer();
        $this->logger = $container
            ->get(LoggerFactory::class)
            ->get('withdraw', 'default');

        // Inicializa repositórios
        try {
            $this->withdrawRepository = $container->get(AccountWithdrawRepositoryInterface::class);
            $this->pixRepository = $container->get(AccountWithdrawPixRepositoryInterface::class);
            $this->accountRepository = $container->get(AccountRepositoryInterface::class);
        } catch (\Throwable $e) {
            $this->withdrawRepository = new AccountWithdrawRepository();
            $this->pixRepository = new AccountWithdrawPixRepository();
            $this->accountRepository = new AccountRepository();
        }
    }

    /**
     * Executa o envio da notificação
     *
     * @throws \Throwable Quando o envio falha
     */
    public function handle(): void
    {
        try {
            // Busca o saque através do repositório (retorna DTO)
            $withdrawData = $this->withdrawRepository->findWithdrawById($this->withdrawId);

            if ($withdrawData === null) {
                $this->logger->error("Saque não encontrado para notificação: {$this->withdrawId}");

                return;
            }

            // Só envia email se o saque foi processado com sucesso
            if (! $withdrawData->isCompleted()) {
                $this->logger->warning("Tentativa de envio de email para saque não processado: {$this->withdrawId}");

                return;
            }

            // Busca dados da conta
            $accountData = $this->accountRepository->findAccountById($withdrawData->accountId);
            if ($accountData === null) {
                $this->logger->error("Conta não encontrada para notificação: {$withdrawData->accountId}");

                return;
            }

            $emailService = ApplicationContext::getContainer()->get(EmailService::class);

            // Verifica se é um saque PIX e busca os dados PIX
            if ($withdrawData->isPixMethod()) {
                $pixData = $this->pixRepository->findByWithdrawId($this->withdrawId);

                if ($pixData !== null && $pixData->type->value === 'email') {
                    // Envia email para a chave PIX (se for email)
                    $emailService->sendWithdrawConfirmationFromDTOs($withdrawData, $accountData, $pixData);
                } else {
                    $this->logger->info("Email não enviado - chave PIX não é email ou dados PIX não encontrados", [
                        'withdraw_id' => $this->withdrawId,
                        'pix_type' => $pixData?->type->value ?? null,
                    ]);
                }
            } else {
                // Para outros métodos, envia notificação genérica
                $emailService->sendGenericWithdrawConfirmation($withdrawData, $accountData);
            }

        } catch (\Throwable $e) {
            $this->logger->error("Erro ao enviar notificação de saque {$this->withdrawId}: {$e->getMessage()}", [
                'exception' => $e,
                'withdraw_id' => $this->withdrawId,
            ]);

            // Re-lança a exceção para que o job seja reprocessado
            throw $e;
        }
    }

    /**
     * Obtém o número máximo de tentativas
     *
     * @return int Máximo de tentativas
     */
    public function getMaxAttempts(): int
    {
        return 3; // Máximo de 3 tentativas
    }

    /**
     * Obtém os intervalos de retry em segundos
     *
     * @return array Intervalos de retry
     */
    public function getRetrySeconds(): array
    {
        return [30, 60, 300]; // Retry em 30s, 60s e 5min
    }
}
