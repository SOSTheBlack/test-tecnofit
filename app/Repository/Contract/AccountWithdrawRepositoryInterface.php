<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;

interface AccountWithdrawRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontra um saque pelo ID
     * 
     * @param string $id
     * @return AccountWithdraw|null
     */
    public function findById(string $id): ?AccountWithdraw;

    /**
     * Encontra um saque pelo transaction_id
     * 
     * @param string $transactionId
     * @return AccountWithdraw|null
     */
    public function findByTransactionId(string $transactionId): ?AccountWithdraw;

    /**
     * Cria um novo saque
     * 
     * @param array $data
     * @return AccountWithdraw
     */
    public function create(array $data): AccountWithdraw;

    /**
     * Cria dados PIX para um saque
     * 
     * @param string $withdrawId
     * @param string $key
     * @param string $type
     * @return AccountWithdrawPix
     */
    public function createPixData(string $withdrawId, string $key, string $type): AccountWithdrawPix;

    /**
     * Atualiza um saque
     * 
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool;

    /**
     * Marca um saque como processando
     * 
     * @param string $id
     * @return bool
     */
    public function markAsProcessing(string $id): bool;

    /**
     * Marca um saque como completado
     * 
     * @param string $id
     * @param array $metadata
     * @return bool
     */
    public function markAsCompleted(string $id, array $metadata = []): bool;

    /**
     * Marca um saque como falhado
     * 
     * @param string $id
     * @param string $errorReason
     * @param array $metadata
     * @return bool
     */
    public function markAsFailed(string $id, string $errorReason, array $metadata = []): bool;
}
