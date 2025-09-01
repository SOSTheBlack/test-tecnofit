<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\AccountWithdraw;
use App\Service\EmailService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class SendWithdrawNotificationJob extends Job
{
    private string $withdrawId;
    private LoggerInterface $logger;

    public function __construct(string $withdrawId)
    {
        $this->withdrawId = $withdrawId;
        $this->logger = ApplicationContext::getContainer()
            ->get(LoggerFactory::class)
            ->get('withdraw', 'default');
    }

    public function handle(): void
    {
        try {
            $withdraw = AccountWithdraw::with(['pixData', 'account'])
                ->where('id', $this->withdrawId)
                ->first();

            if (!$withdraw) {
                $this->logger->error("Saque não encontrado para notificação: {$this->withdrawId}");
                return;
            }

            // Só envia email se o saque foi processado com sucesso
            if ($withdraw->status !== 'completed') {
                $this->logger->warning("Tentativa de envio de email para saque não processado: {$this->withdrawId}");
                return;
            }

            $emailService = ApplicationContext::getContainer()->get(EmailService::class);
            
            // Envia email para a chave PIX (se for email)
            if ($withdraw->pixData && $withdraw->pixData->type === 'email') {
                $emailService->sendWithdrawConfirmation($withdraw);
            } else {
                $this->logger->info("Email não enviado - chave PIX não é email ou dados PIX não encontrados", [
                    'withdraw_id' => $this->withdrawId,
                    'pix_type' => $withdraw->pixData?->type
                ]);
            }

        } catch (\Throwable $e) {
            $this->logger->error("Erro ao enviar notificação de saque {$this->withdrawId}: {$e->getMessage()}", [
                'exception' => $e,
                'withdraw_id' => $this->withdrawId
            ]);
            
            // Re-lança a exceção para que o job seja reprocessado
            throw $e;
        }
    }

    public function getMaxAttempts(): int
    {
        return 3; // Máximo de 3 tentativas
    }

    public function getRetrySeconds(): array
    {
        return [30, 60, 300]; // Retry em 30s, 60s e 5min
    }
}
