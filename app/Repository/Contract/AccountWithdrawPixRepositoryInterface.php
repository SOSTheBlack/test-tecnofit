<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\DataTransfer\Account\Balance\AccountWithdrawPixData;

/**
 * Interface para repositório de dados PIX de saques
 *
 * Gerencia as operações de dados PIX específicos vinculados aos saques
 */
interface AccountWithdrawPixRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontra dados PIX pelo ID do saque
     *
     * @param string $withdrawId ID do saque
     * @return AccountWithdrawPixData|null
     */
    public function findByWithdrawId(string $withdrawId): ?AccountWithdrawPixData;

    /**
     * Cria dados PIX para um saque
     *
     * @param string $withdrawId ID do saque
     * @param string $key Chave PIX
     * @param string $type Tipo da chave PIX
     * @param string|null $externalId ID externo (opcional)
     * @return AccountWithdrawPixData
     * @throws \RuntimeException Quando falha ao criar
     */
    public function createPixData(string $withdrawId, string $key, string $type, ?string $externalId = null): AccountWithdrawPixData;


}
