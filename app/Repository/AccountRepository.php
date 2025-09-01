<?php

declare(strict_types=1);

namespace App\Repository;

use App\DataTransfer\Account\AccountData;
use App\Model\Account;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\ModelNotFoundException;
use Throwable;

/**
 * Repositório para gerenciamento de contas
 *
 * Implementa operações de persistência para contas seguindo o padrão
 * de retornar apenas DTOs, mantendo o modelo isolado na camada de dados
 */
class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    public function __construct(private Account $account = new Account())
    {
    }

    /**
     * Retorna o modelo que este repositório gerencia
     *
     * @return Account
     */
    protected function getModel(): Account
    {
        return $this->account;
    }

    /**
     * {@inheritdoc}
     */
    public function findAccountById(string $accountId): ?AccountData
    {
        /** @var Account|null $account */
        $account = Account::query()->find($accountId);

        return $account ? AccountData::fromModel($account) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAccountByIdOrFail(string $accountId): AccountData
    {
        try {
            /** @var Account $account */
            $account = Account::query()->findOrFail($accountId);

            return AccountData::fromModel($account);
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException('Conta não encontrada.', previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function debitAmount(string $accountId, float $amount): bool
    {
        try {
            return $this->transaction(function () use ($accountId, $amount) {
                /** @var Account $account */
                $account = Account::query()->lockForUpdate()->findOrFail($accountId);

                $newBalance = $account->balance - $amount;

                if ($newBalance < 0) {
                    return false;
                }

                return $account->update(['balance' => $newBalance]);
            });
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException('Conta não encontrada para débito.', previous: $e);
        } catch (Throwable $e) {
            throw new \RuntimeException("Erro ao debitar valor da conta: {$e->getMessage()}", 0, $e);
        }
    }
}
