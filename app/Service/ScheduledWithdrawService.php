<?php

declare(strict_types=1);

namespace App\Service;

use App\Job\ProcessScheduledWithdrawJob;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;

class ScheduledWithdrawService
{
    /**
     * Agenda um job para processar saque na data correta
     */
    public function scheduleWithdrawJob(string $withdrawId, Carbon $scheduledFor): bool
    {
        try {
            $container = ApplicationContext::getContainer();
            $driverFactory = $container->get(DriverFactory::class);
            $driver = $driverFactory->get('default');

            // Calcula o delay em segundos até a data agendada
            $delayInSeconds = max(0, $scheduledFor->diffInSeconds(Carbon::now()));

            // Cria e agenda o job
            $job = new ProcessScheduledWithdrawJob($withdrawId);
            
            // Agenda o job para execução no momento correto
            $driver->push($job, $delayInSeconds);

            echo "Job agendado para saque {$withdrawId} em {$scheduledFor->toISOString()} (delay: {$delayInSeconds}s)\n";
            
            return true;

        } catch (\Throwable $e) {
            echo "Erro ao agendar job para saque {$withdrawId}: {$e->getMessage()}\n";
            return false;
        }
    }

    /**
     * Agenda job para execução imediata (para testes ou saques vencidos)
     */
    public function scheduleImmediateExecution(string $withdrawId): bool
    {
        return $this->scheduleWithdrawJob($withdrawId, Carbon::now());
    }

    /**
     * Processa todos os saques agendados que já passaram da data
     * Útil para execução manual ou cron job
     */
    public function processOverdueWithdraws(): int
    {
        // Este método seria implementado caso necessário para recuperar
        // saques que não foram processados por falha do job scheduler
        // Por enquanto retorna 0 pois o foco é no job assíncrono
        return 0;
    }
}
