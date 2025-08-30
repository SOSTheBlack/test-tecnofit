<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Account\Balance\AccountWithdrawDTO;
use App\Model\AccountWithdraw;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;
use Throwable;

class AccountWithdrawRepository implements AccountWithdrawRepositoryInterface
{
    public function findById(string $id): ?AccountWithdraw
    {
        return AccountWithdraw::find($id);
    }

    public function findByTransactionId(string $transactionId): ?AccountWithdraw
    {
        return AccountWithdraw::where('transaction_id', $transactionId)->first();
    }

    public function create(array $data): AccountWithdraw
    {
        // Gera UUID se não fornecido
        if (!isset($data['id'])) {
            $data['id'] = \Hyperf\Stringable\Str::uuid();
        }

        // Gera transaction_id se não fornecido
        if (!isset($data['transaction_id'])) {
            $data['transaction_id'] = AccountWithdraw::generateTransactionId();
        }

        return AccountWithdraw::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $withdraw = $this->findById($id);
        
        if (!$withdraw) {
            throw new RepositoryNotFoundException("AccountWithdraw com ID {$id} não encontrado.");
        }

        return $withdraw->update($data);
    }

    public function delete(string $id): bool
    {
        $withdraw = $this->findById($id);
        
        if (!$withdraw) {
            throw new RepositoryNotFoundException("AccountWithdraw com ID {$id} não encontrado.");
        }

        return $withdraw->delete();
    }

    public function findByAccountId(string $accountId): Collection
    {
        return AccountWithdraw::where('account_id', $accountId)
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findPendingByAccountId(string $accountId): Collection
    {
        return AccountWithdraw::where('account_id', $accountId)
            ->byStatus(AccountWithdraw::STATUS_PENDING)
            ->where('done', false)
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function findScheduledReadyForExecution(): Collection
    {
        return AccountWithdraw::scheduled()
            ->byStatus(AccountWithdraw::STATUS_PENDING)
            ->where('scheduled_for', '<=', Carbon::now())
            ->with(['account', 'pixData'])
            ->orderBy('scheduled_for', 'asc')
            ->get();
    }

    public function findByStatus(string $status): Collection
    {
        return AccountWithdraw::byStatus($status)
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByMethod(string $method): Collection
    {
        return AccountWithdraw::byMethod($method)
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findScheduledForDate(Carbon $date): Collection
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return AccountWithdraw::scheduled()
            ->whereBetween('scheduled_for', [$startOfDay, $endOfDay])
            ->with(['account', 'pixData'])
            ->orderBy('scheduled_for', 'asc')
            ->get();
    }

    public function getTotalPendingAmountForAccount(string $accountId): float
    {
        return (float) AccountWithdraw::where('account_id', $accountId)
            ->whereIn('status', [
                AccountWithdraw::STATUS_PENDING,
                AccountWithdraw::STATUS_PROCESSING,
                AccountWithdraw::STATUS_SCHEDULED
            ])
            ->sum('amount');
    }

    public function getAccountWithdrawStats(string $accountId): array
    {
        $withdraws = AccountWithdraw::where('account_id', $accountId);

        return [
            'total_count' => $withdraws->count(),
            'total_amount' => (float) $withdraws->sum('amount'),
            'pending_count' => $withdraws->clone()->byStatus(AccountWithdraw::STATUS_PENDING)->count(),
            'pending_amount' => (float) $withdraws->clone()->byStatus(AccountWithdraw::STATUS_PENDING)->sum('amount'),
            'completed_count' => $withdraws->clone()->byStatus(AccountWithdraw::STATUS_COMPLETED)->count(),
            'completed_amount' => (float) $withdraws->clone()->byStatus(AccountWithdraw::STATUS_COMPLETED)->sum('amount'),
            'failed_count' => $withdraws->clone()->byStatus(AccountWithdraw::STATUS_FAILED)->count(),
            'failed_amount' => (float) $withdraws->clone()->byStatus(AccountWithdraw::STATUS_FAILED)->sum('amount'),
            'scheduled_count' => $withdraws->clone()->scheduled()->count(),
            'scheduled_amount' => (float) $withdraws->clone()->scheduled()->sum('amount'),
        ];
    }

    public function getWithdrawHistory(string $accountId, int $days = 30): Collection
    {
        $startDate = Carbon::now()->subDays($days);

        return AccountWithdraw::where('account_id', $accountId)
            ->where('created_at', '>=', $startDate)
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByMinimumAmount(float $minimumAmount): Collection
    {
        return AccountWithdraw::where('amount', '>=', $minimumAmount)
            ->with(['account', 'pixData'])
            ->orderBy('amount', 'desc')
            ->get();
    }

    public function findByDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return AccountWithdraw::whereBetween('created_at', [$startDate, $endDate])
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function hasPendingWithdrawForAccount(string $accountId): bool
    {
        return AccountWithdraw::where('account_id', $accountId)
            ->whereIn('status', [
                AccountWithdraw::STATUS_PENDING,
                AccountWithdraw::STATUS_PROCESSING,
                AccountWithdraw::STATUS_SCHEDULED
            ])
            ->exists();
    }

    public function findFailedForRetry(): Collection
    {
        return AccountWithdraw::byStatus(AccountWithdraw::STATUS_FAILED)
            ->where('error', true)
            ->whereNotNull('error_reason')
            ->with(['account', 'pixData'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getCountByStatus(): array
    {
        return Db::table('account_withdraw')
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total_amount' => (float) $item->total_amount
                ]];
            })
            ->toArray();
    }

    public function findWithPagination(int $page = 1, int $perPage = 20): array
    {
        $query = AccountWithdraw::with(['account', 'pixData'])
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $items = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => (($page - 1) * $perPage) + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    public function findByFilters(array $filters): Collection
    {
        $query = AccountWithdraw::query();

        // Filtro por conta
        if (isset($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        // Filtro por status
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Filtro por método
        if (isset($filters['method'])) {
            $query->byMethod($filters['method']);
        }

        // Filtro por valor mínimo
        if (isset($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        // Filtro por valor máximo
        if (isset($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Filtro por data de criação
        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['created_from']));
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['created_to']));
        }

        // Filtro por agendamento
        if (isset($filters['scheduled'])) {
            if ($filters['scheduled']) {
                $query->scheduled();
            } else {
                $query->immediate();
            }
        }

        // Filtro por transaction_id
        if (isset($filters['transaction_id'])) {
            $query->where('transaction_id', 'like', '%' . $filters['transaction_id'] . '%');
        }

        // Filtro por erro
        if (isset($filters['has_error'])) {
            $query->where('error', $filters['has_error']);
        }

        return $query->with(['account', 'pixData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Métodos auxiliares para operações específicas
     */

    /**
     * Marca um saque como processando
     */
    public function markAsProcessing(string $id): bool
    {
        return $this->update($id, [
            'status' => AccountWithdraw::STATUS_PROCESSING,
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Marca um saque como completado
     */
    public function markAsCompleted(string $id, array $metadata = []): bool
    {
        $updateData = [
            'status' => AccountWithdraw::STATUS_COMPLETED,
            'done' => true,
            'error' => false,
            'error_reason' => null,
            'updated_at' => Carbon::now(),
        ];

        if (!empty($metadata)) {
            $withdraw = $this->findById($id);
            $updateData['meta'] = array_merge($withdraw?->meta ?? [], $metadata);
        }

        return $this->update($id, $updateData);
    }

    /**
     * Marca um saque como falhado
     */
    public function markAsFailed(string $id, string $errorReason, array $metadata = []): bool
    {
        $updateData = [
            'status' => AccountWithdraw::STATUS_FAILED,
            'error' => true,
            'error_reason' => $errorReason,
            'done' => false,
            'updated_at' => Carbon::now(),
        ];

        if (!empty($metadata)) {
            $withdraw = $this->findById($id);
            $updateData['meta'] = array_merge($withdraw?->meta ?? [], $metadata);
        }

        return $this->update($id, $updateData);
    }

    /**
     * Cancela um saque
     */
    public function cancel(string $id, string $reason = 'Cancelled by user'): bool
    {
        return $this->update($id, [
            'status' => AccountWithdraw::STATUS_CANCELLED,
            'error_reason' => $reason,
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Busca saques que precisam ser processados (agendados + pendentes)
     */
    public function findReadyForProcessing(): Collection
    {
        return AccountWithdraw::where(function ($query) {
            // Saques imediatos pendentes
            $query->where('scheduled', false)
                ->where('status', AccountWithdraw::STATUS_PENDING);
        })
        ->orWhere(function ($query) {
            // Saques agendados prontos
            $query->where('scheduled', true)
                ->where('status', AccountWithdraw::STATUS_PENDING)
                ->where('scheduled_for', '<=', Carbon::now());
        })
        ->with(['account', 'pixData'])
        ->orderBy('created_at', 'asc')
        ->get();
    }

    /**
     * Obtém relatório consolidado de saques
     */
    public function getConsolidatedReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = AccountWithdraw::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalWithdraws = $query->count();
        $totalAmount = (float) $query->sum('amount');

        $byStatus = $query->clone()
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => [
                'count' => $item->count,
                'amount' => (float) $item->amount
            ]]);

        $byMethod = $query->clone()
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('method')
            ->get()
            ->mapWithKeys(fn($item) => [$item->method => [
                'count' => $item->count,
                'amount' => (float) $item->amount
            ]]);

        return [
            'period' => [
                'start_date' => $startDate?->toISOString(),
                'end_date' => $endDate?->toISOString(),
            ],
            'totals' => [
                'count' => $totalWithdraws,
                'amount' => $totalAmount,
            ],
            'by_status' => $byStatus,
            'by_method' => $byMethod,
        ];
    }

    // DTO Methods

    /**
     * Converte model para DTO
     */
    public function toDTO(AccountWithdraw $model): AccountWithdrawDTO
    {
        return AccountWithdrawDTO::fromModel($model);
    }

    /**
     * Converte array de models para DTOs
     */
    public function toDTOs(array $models): array
    {
        return array_map(fn(AccountWithdraw $model) => $this->toDTO($model), $models);
    }

    /**
     * Encontra um saque pelo ID e retorna como DTO
     */
    public function findByIdAsDTO(string $id): ?AccountWithdrawDTO
    {
        $model = $this->findById($id);
        return $model ? $this->toDTO($model) : null;
    }

    /**
     * Encontra um saque pelo transaction_id e retorna como DTO
     */
    public function findByTransactionIdAsDTO(string $transactionId): ?AccountWithdrawDTO
    {
        $model = $this->findByTransactionId($transactionId);
        return $model ? $this->toDTO($model) : null;
    }

    /**
     * Obtém saques de uma conta como DTOs
     */
    public function findByAccountIdAsDTO(string $accountId): array
    {
        $models = $this->findByAccountId($accountId)->all();
        return $this->toDTOs($models);
    }

    /**
     * Obtém saques pendentes de uma conta como DTOs
     */
    public function findPendingByAccountIdAsDTO(string $accountId): array
    {
        $models = $this->findPendingByAccountId($accountId)->all();
        return $this->toDTOs($models);
    }

    /**
     * Obtém saques por status como DTOs
     */
    public function findByStatusAsDTO(string $status): array
    {
        $models = $this->findByStatus($status)->all();
        return $this->toDTOs($models);
    }
}
