<?php

declare(strict_types=1);

namespace App\Repository;

use App\DataTransfer\Account\Balance\AccountWithdrawData;
use App\DataTransfer\Account\Balance\AccountWithdrawPixData;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Stringable\Str;
use Throwable;

/**
 * Repositório para gerenciamento de saques
 *
 * Implementa operações de persistência para saques seguindo o padrão
 * de retornar apenas DTOs, mantendo o modelo isolado na camada de dados
 */
class AccountWithdrawRepository extends BaseRepository implements AccountWithdrawRepositoryInterface
{
    public function __construct(private AccountWithdraw $accountWithdraw = new AccountWithdraw())
    {
    }

    /**
     * Retorna o modelo que este repositório gerencia
     *
     * @return AccountWithdraw
     */
    protected function getModel(): AccountWithdraw
    {
        return $this->accountWithdraw;
    }

    /**
     * {@inheritdoc}
     */
    public function findWithdrawById(string $id): ?AccountWithdrawData
    {
        /** @var AccountWithdraw|null $withdraw */
        $withdraw = AccountWithdraw::query()->find($id);

        return $withdraw ? AccountWithdrawData::fromModel($withdraw) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findWithdrawByIdOrFail(string $id): AccountWithdrawData
    {
        /** @var AccountWithdraw|null $withdraw */
        $withdraw = AccountWithdraw::query()->find($id);

        if (! $withdraw) {
            throw new RepositoryNotFoundException("Saque com ID '{$id}' não encontrado.");
        }

        return AccountWithdrawData::fromModel($withdraw);
    }

    /**
     * {@inheritdoc}
     */
    public function findByTransactionId(string $transactionId): ?AccountWithdrawData
    {
        /** @var AccountWithdraw|null $withdraw */
        $withdraw = AccountWithdraw::query()->where('transaction_id', $transactionId)->first();

        return $withdraw ? AccountWithdrawData::fromModel($withdraw) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function createWithdraw(array $data): AccountWithdrawData
    {
        try {
            return $this->transaction(function () use ($data) {
                // Gera UUID se não fornecido
                if (! isset($data['id'])) {
                    $data['id'] = (string) Str::uuid();
                }

                // Gera transaction_id se não fornecido
                if (! isset($data['transaction_id'])) {
                    $data['transaction_id'] = 'TXN-' . time() . '-' . substr(md5(uniqid()), 0, 8);
                }

                // Assegura valores padrão
                $data = array_merge([
                    'status' => AccountWithdraw::STATUS_PENDING,
                    'done' => false,
                    'error' => false,
                    'scheduled' => false,
                    'meta' => [],
                ], $data);

                /** @var AccountWithdraw $withdraw */
                $withdraw = AccountWithdraw::query()->create($data);

                return AccountWithdrawData::fromModel($withdraw);
            });
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Erro ao criar saque: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createPixData(string $withdrawId, string $key, string $type): AccountWithdrawPixData
    {
        try {
            return $this->transaction(function () use ($withdrawId, $key, $type) {
                /** @var AccountWithdrawPix $pixData */
                $pixData = AccountWithdrawPix::query()->create([
                    'id' => (string) Str::uuid(),
                    'account_withdraw_id' => $withdrawId,
                    'key' => $key,
                    'type' => $type,
                ]);

                return AccountWithdrawPixData::fromModel($pixData);
            });
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Erro ao criar dados PIX: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateWithdraw(string $id, array $data): bool
    {
        /** @var AccountWithdraw|null $withdraw */
        $withdraw = AccountWithdraw::query()->find($id);

        if (! $withdraw) {
            throw new RepositoryNotFoundException("AccountWithdraw com ID {$id} não encontrado.");
        }

        return $withdraw->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function markAsCompleted(string $id, array $metadata = []): bool
    {
        $updateData = [
            'status' => AccountWithdraw::STATUS_COMPLETED,
            'done' => true,
            'error' => false,
            'error_reason' => null,
            'updated_at' => timezone()->now(),
        ];

        if (! empty($metadata)) {
            $withdraw = $this->findWithdrawById($id);
            $updateData['meta'] = array_merge($withdraw?->meta ?? [], $metadata);
        }

        return $this->updateWithdraw($id, $updateData);
    }

    /**
     * {@inheritdoc}
     */
    public function markAsFailed(string $id, string $errorReason, array $metadata = []): bool
    {
        $updateData = [
            'status' => AccountWithdraw::STATUS_FAILED,
            'error' => true,
            'error_reason' => $errorReason,
            'done' => false,
            'updated_at' => timezone()->now(),
        ];

        if (! empty($metadata)) {
            $withdraw = $this->findWithdrawById($id);
            $updateData['meta'] = array_merge($withdraw?->meta ?? [], $metadata);
        }

        return $this->updateWithdraw($id, $updateData);
    }

    /**
     * {@inheritdoc}
     */
    public function findScheduledReady(int $limit = 100): array
    {
        /** @var \Hyperf\Database\Model\Collection $withdraws */
        $withdraws = AccountWithdraw::query()
            ->where('scheduled', true)
            ->where('status', AccountWithdraw::STATUS_PENDING)
            ->where('done', false)
            ->where('scheduled_for', '<=', timezone()->now())
            ->limit($limit)
            ->orderBy('scheduled_for', 'asc')
            ->get();

        return $withdraws->map(static function ($withdraw): AccountWithdrawData {
            \assert($withdraw instanceof AccountWithdraw);

            return AccountWithdrawData::fromModel($withdraw);
        })->toArray();
    }

    /**
     * Obtém estatísticas de saques
     *
     * @param string|null $accountId ID da conta (opcional)
     * @return array Estatísticas dos saques
     */
    public function getWithdrawStatistics(?string $accountId = null): array
    {
        $query = AccountWithdraw::query();

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_withdraws,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount,
            MAX(amount) as max_amount,
            MIN(amount) as min_amount,
            COUNT(CASE WHEN status = ? THEN 1 END) as completed_count,
            COUNT(CASE WHEN status = ? THEN 1 END) as failed_count,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending_count
        ', [
            AccountWithdraw::STATUS_COMPLETED,
            AccountWithdraw::STATUS_FAILED,
            AccountWithdraw::STATUS_PENDING,
        ])->first();

        return [
            'total_withdraws' => (int) ($stats->total_withdraws ?? 0),
            'total_amount' => (float) ($stats->total_amount ?? 0),
            'average_amount' => (float) ($stats->average_amount ?? 0),
            'max_amount' => (float) ($stats->max_amount ?? 0),
            'min_amount' => (float) ($stats->min_amount ?? 0),
            'completed_count' => (int) ($stats->completed_count ?? 0),
            'failed_count' => (int) ($stats->failed_count ?? 0),
            'pending_count' => (int) ($stats->pending_count ?? 0),
        ];
    }
}
