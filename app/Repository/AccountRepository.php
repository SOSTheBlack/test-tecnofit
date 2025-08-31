<?php

declare(strict_types=1);

namespace App\Repository;

use App\DataTransfer\Account\AccountData;
use App\Model\Account;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\ModelNotFoundException;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(private Account $account = new Account())
    {

    }

    public function findById(string $accountId): ?Account
    {
        try {
            return $this->account->findOrFail($accountId);
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException('Conta não encontrada.', previous: $e);
        }
    }

    public function debitAmount(string $accountId, float $amount): bool
    {
        $account = $this->findById($accountId);
        
        if (!$account) {
            return false;
        }

        $newBalance = $account->balance - $amount;
        
        if ($newBalance < 0) {
            return false;
        }

        $account->balance = $newBalance;
        return $account->save();
    }
}
