<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Model\AccountWithdraw;

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
}
