<?php

declare(strict_types=1);

namespace App\Job\Account\Balance;

use App\DataTransfer\Account\Balance\WithdrawRequestData;
use App\Repository\AccountWithdrawPixRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\Contract\AccountWithdrawPixRepositoryInterface;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\UseCase\Account\Balance\WithdrawUseCase;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Throwable;

/**
 * Job assíncrono para processamento de saques agendados
 * 
 * Responsável por executar saques que foram agendados para uma data/hora específica.
 * Segue o padrão de não utilizar models diretamente, apenas DTOs e repositórios.
 */
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

    public function __construct(
        string $withdrawId, 
        private ?AccountWithdrawRepositoryInterface $accountWithdrawRepository = null,
        private ?AccountWithdrawPixRepositoryInterface $accountWithdrawPixRepository = null
    ) {
        $this->withdrawId = $withdrawId;

        $container = ApplicationContext::getContainer();
        try {
            $this->accountWithdrawRepository = $container->get(AccountWithdrawRepositoryInterface::class);
            $this->accountWithdrawPixRepository = $container->get(AccountWithdrawPixRepositoryInterface::class);
        } catch (Throwable $e) {
            $this->accountWithdrawRepository = new AccountWithdrawRepository();
            $this->accountWithdrawPixRepository = new AccountWithdrawPixRepository();
        }
    }

    /**
     * Executa o processamento do saque agendado
     * 
     * @throws \RuntimeException Quando o processamento falha
     */
    public function handle(): void
    {
        try {
            // Busca o saque agendado através do repositório (retorna DTO)
            $withdrawData = $this->accountWithdrawRepository?->findWithdrawById($this->withdrawId);
            if (!$withdrawData) {
                throw new \RuntimeException("Saque agendado não encontrado: {$this->withdrawId}");
            }

            // Busca dados PIX se for um saque PIX
            $pixData = null;
            if ($withdrawData->isPixMethod()) {
                $pixDataDto = $this->accountWithdrawPixRepository?->findByWithdrawId($this->withdrawId);
                $pixData = $pixDataDto?->toPixData();
            }

            // Cria WithdrawRequestData a partir do DTO do saque
            $withdrawRequestData = WithdrawRequestData::fromAccountWithdrawData($withdrawData, $pixData);

            // Executa usando o WithdrawUseCase (que processará como imediato)
            $useCase = new WithdrawUseCase();
            $result = $useCase->execute($withdrawRequestData);

            // Interpreta o resultado e age conforme necessário
            if (!$result->success) {
                $errorMessage = $result->message ?? 'Erro desconhecido no processamento';
                
                // Marca o saque original como falha
                $this->accountWithdrawRepository?->markAsFailed(
                    $this->withdrawId,
                    "Falha na execução agendada: {$errorMessage}"
                );

                throw new \RuntimeException("Falha ao processar saque agendado: {$errorMessage} - " . json_encode($result));
            }

            echo "Saque agendado processado com sucesso: {$this->withdrawId} (transação: {$result->transactionId})\n";

        } catch (Throwable $exception) {
            echo "Erro ao processar saque agendado {$this->withdrawId}: {$exception->getMessage()}\n";
            
            // Marca como falha se não conseguir processar
            try {
                $withdrawRepository = $this->accountWithdrawRepository ?? new AccountWithdrawRepository();
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
     * 
     * @param Throwable $exception Exceção que causou a falha
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
