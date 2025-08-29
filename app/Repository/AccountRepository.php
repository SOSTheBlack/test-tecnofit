<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Account;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Exceptions\RepositoryException;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Exception;
use Hyperf\Database\Model\ModelNotFoundException;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(private Account $account = new Account())
    {

    }

    public function findById(string $accountId): ?Account
    {
        return $this->account->findOrFail($accountId);
    }

    public function getBalance(string $accountId): ?float
    {
        $account = $this->findById($accountId);
        
        if (!$account) {
            return null;
        }

        return (float) $account->balance;
    }

    public function hasSufficientBalance(string $accountId, float $amount): bool
    {
        $balance = $this->getBalance($accountId);
        
        if ($balance === null) {
            return false;
        }

        return $balance >= $amount;
    }

    public function updateBalance(string $accountId, float $newBalance): bool
    {
        $account = $this->findById($accountId);
        
        if (!$account) {
            return false;
        }

        $account->balance = $newBalance;
        return $account->save();
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

    public function create(array $data): Account
    {
        return Account::create($data);
    }

    public function update(string $accountId, array $data): bool
    {
        $account = $this->findById($accountId);
        
        if (!$account) {
            return false;
        }

        return $account->update($data);
    }

    public function delete(string $accountId): bool
    {
        $account = $this->findById($accountId);
        
        if (!$account) {
            return false;
        }

        return $account->delete();
    }

    public function findAll(): array
    {
        return Account::all()->toArray();
    }

    public function findByName(string $name): array
    {
        return Account::where('name', 'like', "%{$name}%")->get()->toArray();
    }
}
