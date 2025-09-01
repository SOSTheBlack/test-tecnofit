<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Model\Account;

interface AccountRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontra uma conta pelo ID
     * 
     * @param string $accountId
     * @return Account|null
     * @throws \App\Repository\Exceptions\RepositoryNotFoundException
     */
    public function findById(string $accountId): ?Account;

    /**
     * Debita um valor da conta
     * 
     * @param string $accountId
     * @param float $amount
     * @return bool
     */
    public function debitAmount(string $accountId, float $amount): bool;
}
