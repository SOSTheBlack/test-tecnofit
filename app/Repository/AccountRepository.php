<?php

declare(strict_types=1);

namespace App\Repository;

use App\DataTransfer\Account\AccountData;
use App\Model\Account;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Stringable\Str;
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
    public function findById(string $accountId): ?AccountData
    {
        /** @var Account|null $account */
        $account = Account::query()->find($accountId);
        
        return $account ? AccountData::fromModel($account) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIdOrFail(string $accountId): AccountData
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

    /**
     * {@inheritdoc}
     */
    public function creditAmount(string $accountId, float $amount): bool
    {
        try {
            return $this->transaction(function () use ($accountId, $amount) {
                /** @var Account $account */
                $account = Account::query()->lockForUpdate()->findOrFail($accountId);
                
                $newBalance = $account->balance + $amount;
                
                return $account->update(['balance' => $newBalance]);
            });
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException('Conta não encontrada para crédito.', previous: $e);
        } catch (Throwable $e) {
            throw new \RuntimeException("Erro ao creditar valor na conta: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateAccount(string $accountId, array $data): bool
    {
        try {
            /** @var Account $account */
            $account = Account::query()->findOrFail($accountId);
            
            return $account->update($data);
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException('Conta não encontrada para atualização.', previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createAccount(array $data): AccountData
    {
        try {
            return $this->transaction(function () use ($data) {
                // Gera UUID se não fornecido
                if (!isset($data['id'])) {
                    $data['id'] = (string) Str::uuid();
                }

                // Define valores padrão
                $data = array_merge([
                    'balance' => 0.0,
                ], $data);

                /** @var Account $account */
                $account = Account::query()->create($data);
                
                return AccountData::fromModel($account);
            });
        } catch (Throwable $e) {
            throw new \RuntimeException("Erro ao criar conta: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function accountExists(string $accountId): bool
    {
        return Account::query()->where('id', $accountId)->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function listAccounts(array $criteria = [], int $page = 1, int $perPage = 15): array
    {
        $query = Account::query();

        // Aplica critérios de busca
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $offset = ($page - 1) * $perPage;
        
        /** @var \Hyperf\Database\Model\Collection $accounts */
        $accounts = $query->offset($offset)
            ->limit($perPage)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $query->count();

        return [
            'data' => $accounts->map(function (Account $account) {
                return AccountData::fromModel($account);
            })->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Busca conta por nome
     * 
     * @param string $name Nome da conta
     * @return AccountData|null DTO da conta ou null se não encontrada
     */
    public function findByName(string $name): ?AccountData
    {
        /** @var Account|null $account */
        $account = Account::query()->where('name', $name)->first();
        
        return $account ? AccountData::fromModel($account) : null;
    }

    /**
     * Lista contas com saldo acima de um valor
     * 
     * @param float $minBalance Saldo mínimo
     * @return array Lista de DTOs de contas
     */
    public function findWithBalanceAbove(float $minBalance): array
    {
        /** @var \Hyperf\Database\Model\Collection $accounts */
        $accounts = Account::query()
            ->where('balance', '>', $minBalance)
            ->orderBy('balance', 'desc')
            ->get();

        return $accounts->map(function (Account $account) {
            return AccountData::fromModel($account);
        })->toArray();
    }

    /**
     * Obtém estatísticas gerais das contas
     * 
     * @return array Estatísticas das contas
     */
    public function getAccountStatistics(): array
    {
        $stats = Account::query()
            ->selectRaw('
                COUNT(*) as total_accounts,
                SUM(balance) as total_balance,
                AVG(balance) as average_balance,
                MAX(balance) as max_balance,
                MIN(balance) as min_balance
            ')
            ->first();

        return [
            'total_accounts' => (int) ($stats->total_accounts ?? 0),
            'total_balance' => (float) ($stats->total_balance ?? 0),
            'average_balance' => (float) ($stats->average_balance ?? 0),
            'max_balance' => (float) ($stats->max_balance ?? 0),
            'min_balance' => (float) ($stats->min_balance ?? 0),
        ];
    }
}
