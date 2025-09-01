<?php

declare(strict_types=1);

namespace App\Service;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\Job\Account\Balance\SendWithdrawNotificationJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * Serviço responsável pelo gerenciamento de notificações de saque
 * 
 * Isola a lógica de notificação do caso de uso principal,
 * seguindo o princípio da responsabilidade única
 */
class WithdrawNotificationService
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? ApplicationContext::getContainer()
            ->get(\Hyperf\Logger\LoggerFactory::class)
            ->get('withdraw', 'default');
    }

    /**
     * Agenda notificação de email para saque concluído
     * 
     * @param string $withdrawId ID do saque
     * @param WithdrawRequestData $withdrawRequestData Dados da solicitação
     * @return bool True se agendado com sucesso, false caso contrário
     */
    public function scheduleEmailNotification(string $withdrawId, WithdrawRequestData $withdrawRequestData): bool
    {
        try {
            // Só agenda email se a chave PIX for do tipo email
            if (!$this->shouldSendEmailNotification($withdrawRequestData)) {
                $this->logger->info("Email não enviado - chave PIX não é email", [
                    'withdraw_id' => $withdrawId,
                    'pix_type' => $withdrawRequestData->getPixType()
                ]);
                return true; // Retorna true pois não é um erro
            }

            $success = $this->scheduleNotificationJob($withdrawId);

            if ($success) {
                $this->logger->info("Job de notificação de email agendado", [
                    'withdraw_id' => $withdrawId,
                    'pix_email' => $withdrawRequestData->getPixKey()
                ]);
            }

            return $success;

        } catch (\Throwable $e) {
            $this->logger->error("Falha ao agendar notificação de email", [
                'withdraw_id' => $withdrawId,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            
            // Não propaga erro pois notificação é secundária
            return false;
        }
    }

    /**
     * Verifica se deve enviar notificação por email
     */
    private function shouldSendEmailNotification(WithdrawRequestData $withdrawRequestData): bool
    {
        return $withdrawRequestData->getPixType() === 'email';
    }

    /**
     * Agenda o job de notificação
     */
    private function scheduleNotificationJob(string $withdrawId): bool
    {
        try {
            $driverFactory = ApplicationContext::getContainer()->get(DriverFactory::class);
            $driver = $driverFactory->get('default');

            $job = new SendWithdrawNotificationJob($withdrawId);
            
            // Agenda para execução imediata (delay de 0 segundos)
            $driver->push($job, 0);

            return true;

        } catch (\Throwable $e) {
            $this->logger->error("Erro ao agendar job de notificação", [
                'withdraw_id' => $withdrawId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}