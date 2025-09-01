<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Repository\Contract\AccountWithdrawRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use Throwable;

class AccountWithdrawRepository extends BaseRepository implements AccountWithdrawRepositoryInterface
{
    public function __construct(private AccountWithdraw $accountWithdraw = new AccountWithdraw())
    {
    }

    /**
     * Retorna o modelo que este repositório gerencia
     */
    protected function getModel(): AccountWithdraw
    {
        return $this->accountWithdraw;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?AccountWithdraw
    {
        return AccountWithdraw::find($id);
    }

    /**
     * Encontra um saque pelo transaction_id
     */
    public function findByTransactionId(string $transactionId): ?AccountWithdraw
    {
        return AccountWithdraw::where('transaction_id', $transactionId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): AccountWithdraw
    {
        // Gera UUID se não fornecido
        if (!isset($data['id'])) {
            $data['id'] = (string) Str::uuid();
        }

        // Gera transaction_id se não fornecido
        if (!isset($data['transaction_id'])) {
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

        try {
            return $this->transaction(function () use ($data) {
                return AccountWithdraw::create($data);
            });
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Erro ao criar saque: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Cria dados PIX para um saque
     */
    public function createPixData(string $withdrawId, string $key, string $type): AccountWithdrawPix
    {
        return AccountWithdrawPix::create([
            'id' => (string) Str::uuid(),
            'account_withdraw_id' => $withdrawId,
            'key' => $key,
            'type' => $type,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): bool
    {
        $withdraw = $this->findById($id);
        
        if (!$withdraw) {
            throw new RepositoryNotFoundException("AccountWithdraw com ID {$id} não encontrado.");
        }

        return $withdraw->update($data);
    }

    /**
     * Marca um saque como processando
     */
    public function markAsProcessing(string $id): bool
    {
        return $this->update($id, [
            'status' => AccountWithdraw::STATUS_PROCESSING,
            'updated_at' => timezone()->now(),
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
            'updated_at' => timezone()->now(),
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
            'updated_at' => timezone()->now(),
        ];

        if (!empty($metadata)) {
            $withdraw = $this->findById($id);
            $updateData['meta'] = array_merge($withdraw?->meta ?? [], $metadata);
        }

        return $this->update($id, $updateData);
    }
}
