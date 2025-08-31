<?php

declare(strict_types=1);

namespace App\Job;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\UseCase\Account\Balance\WithdrawUseCase;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Throwable;

class ProcessScheduledWithdrawJob extends Job
{
    /**
     * ID do saque agendado a ser processado
     */
    public string $withdrawId;

    /**
     * Máximo de tentativas em caso de falha
     */
    public int $maxAttempts = 3;

    public function __construct(string $withdrawId, private ?AccountWithdrawRepositoryInterface $accountWithdrawRepository = null)
    {
        $this->withdrawId = $withdrawId;

        $container = ApplicationContext::getContainer();
        try {
            $this->accountWithdrawRepository = $container->get(AccountWithdrawRepositoryInterface::class);
        } catch (Throwable $e) {
            $this->accountWithdrawRepository = new AccountWithdrawRepository();
        }
    }

    public function handle(): void
    {
        try {
            // Busca o saque agendado
            $withdraw = $this->accountWithdrawRepository->findById($this->withdrawId);
            if (!$withdraw) {
                throw new \RuntimeException("Saque agendado não encontrado: {$this->withdrawId}");
            }

            // Executa usando o WithdrawUseCase (que processará como imediato)
            $useCase = new WithdrawUseCase();
            $result = $useCase->execute(WithdrawRequestData::fromModel($withdraw));

            // Interpreta o resultado e age conforme necessário
            if (!$result->success) {
                $errorMessage = $result->message ?? 'Erro desconhecido no processamento';
                
                // Marca o saque original como falha
                $this->accountWithdrawRepository->markAsFailed(
                    $this->withdrawId,
                    "Falha na execução agendada: {$errorMessage}"
                );

                throw new \RuntimeException("Falha ao processar saque agendado: {$errorMessage}");
            }

            echo "Saque agendado processado com sucesso: {$this->withdrawId} (nova transação: {$result->transactionId})\n";

        } catch (Throwable $exception) {
            echo "Erro ao processar saque agendado {$this->withdrawId}: {$exception->getMessage()}\n";
            
            // Marca como falha se não conseguir processar
            try {
                $withdrawRepository = $withdrawRepository ?? new AccountWithdrawRepository();
                $withdrawRepository->markAsFailed(
                    $this->withdrawId,
                    "Erro no job assíncrono: {$exception->getMessage()}"
                );
            } catch (Throwable $markFailError) {
                echo "Erro ao marcar saque como falha: {$markFailError->getMessage()}\n";
            }

            throw $exception;
        }
    }

    /**
     * Método chamado quando o job falha após todas as tentativas
     */
    public function failed(Throwable $exception): void
    {
        echo "Job falhou após {$this->maxAttempts} tentativas: {$exception->getMessage()}\n";
        
        try {
            $withdrawRepository = new AccountWithdrawRepository();
            $withdrawRepository->markAsFailed(
                $this->withdrawId,
                "Job falhou após {$this->maxAttempts} tentativas: {$exception->getMessage()}"
            );
        } catch (Throwable $e) {
            echo "Erro ao marcar saque como falha no método failed(): {$e->getMessage()}\n";
        }
    }
}
