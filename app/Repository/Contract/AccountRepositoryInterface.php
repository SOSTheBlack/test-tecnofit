<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\DataTransfer\Account\AccountData;
use App\Model\Account;

interface AccountRepositoryInterface
{
    /**
     * @param string $accountId
     * 
     * @return Account|null
     * 
     * @throws RepositoryNotFoundException
     */
    public function findById(string $accountId): ?Account;

    public function debitAmount(string $accountId, float $amount): bool;
}
