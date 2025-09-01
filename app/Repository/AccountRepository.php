<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Account;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\ModelNotFoundException;

class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    public function __construct(private Account $account = new Account())
    {
    }

    /**
     * Retorna o modelo que este repositório gerencia
     */
    protected function getModel(): Account
    {
        return $this->account;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $accountId): ?Account
    {
        try {
            /** @var Account $account */
            $account = Account::query()->findOrFail($accountId);
            return $account;
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException('Conta não encontrada.', previous: $e);
        }
    }

    /**
     * Debita um valor da conta
     */
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
