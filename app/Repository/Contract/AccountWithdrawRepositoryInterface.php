<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\Model\AccountWithdraw;
use Carbon\Carbon;
use Hyperf\Database\Model\Collection;

interface AccountWithdrawRepositoryInterface
{
    /**
     * Encontra um saque pelo ID
     */
    public function findById(string $id): ?AccountWithdraw;

    /**
     * Encontra um saque pelo transaction_id
     */
    public function findByTransactionId(string $transactionId): ?AccountWithdraw;

    /**
     * Cria um novo saque
     */
    public function create(array $data): AccountWithdraw;

    /**
     * Atualiza um saque
     */
    public function update(string $id, array $data): bool;

    /**
     * Remove um saque (soft delete)
     */
    public function delete(string $id): bool;

    /**
     * Obtém todos os saques de uma conta
     */
    public function findByAccountId(string $accountId): Collection;

    /**
     * Obtém saques pendentes de uma conta
     */
    public function findPendingByAccountId(string $accountId): Collection;

    /**
     * Obtém saques agendados prontos para execução
     */
    public function findScheduledReadyForExecution(): Collection;

    /**
     * Obtém saques por status
     */
    public function findByStatus(string $status): Collection;

    /**
     * Obtém saques por método
     */
    public function findByMethod(string $method): Collection;

    /**
     * Obtém saques agendados para uma data específica
     */
    public function findScheduledForDate(Carbon $date): Collection;

    /**
     * Obtém total de saques pendentes para uma conta
     */
    public function getTotalPendingAmountForAccount(string $accountId): float;

    /**
     * Obtém estatísticas de saques por conta
     */
    public function getAccountWithdrawStats(string $accountId): array;

    /**
     * Obtém histórico de saques (últimos N dias)
     */
    public function getWithdrawHistory(string $accountId, int $days = 30): Collection;

    /**
     * Obtém saques com valor acima de um limite
     */
    public function findByMinimumAmount(float $minimumAmount): Collection;

    /**
     * Obtém saques criados em um período
     */
    public function findByDateRange(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Verifica se existe saque pendente para uma conta
     */
    public function hasPendingWithdrawForAccount(string $accountId): bool;

    /**
     * Obtém saques falhados que podem ser reprocessados
     */
    public function findFailedForRetry(): Collection;

    /**
     * Obtém contagem de saques por status
     */
    public function getCountByStatus(): array;

    /**
     * Obtém saques com paginação
     */
    public function findWithPagination(int $page = 1, int $perPage = 20): array;

    /**
     * Busca saques por filtros múltiplos
     */
    public function findByFilters(array $filters): Collection;

    /**
     * Cancela um saque
     */
    public function cancel(string $id, string $reason = 'Cancelled by user'): bool;

    /**
     * Obtém relatório consolidado de saques
     */
    public function getConsolidatedReport(?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array;

    /**
     * Marca um saque como processando
     */
    public function markAsProcessing(string $id): bool;

    /**
     * Marca um saque como completado
     */
    public function markAsCompleted(string $id, array $metadata = []): bool;

    /**
     * Marca um saque como falhado
     */
    public function markAsFailed(string $id, string $errorReason, array $metadata = []): bool;

    /**
     * Busca saques que precisam ser processados
     */
    public function findReadyForProcessing(): Collection;

    // DTO Methods
    
    /**
     * Converte model para DTO
     */
    public function toDTO(AccountWithdraw $model): AccountWithdrawData;

    /**
     * Converte array de models para DTOs
     */
    public function toDTOs(array $models): array;

    /**
     * Encontra um saque pelo ID e retorna como DTO
     */
    public function findByIdAsDTO(string $id): ?AccountWithdrawData;

    /**
     * Encontra um saque pelo transaction_id e retorna como DTO
     */
    public function findByTransactionIdAsDTO(string $transactionId): ?AccountWithdrawData;

    /**
     * Obtém saques de uma conta como DTOs
     */
    public function findByAccountIdAsDTO(string $accountId): array;

    /**
     * Obtém saques pendentes de uma conta como DTOs
     */
    public function findPendingByAccountIdAsDTO(string $accountId): array;

    /**
     * Obtém saques por status como DTOs
     */
    public function findByStatusAsDTO(string $status): array;
}
