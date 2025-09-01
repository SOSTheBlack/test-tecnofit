<?php

declare(strict_types=1);

namespace App\Repository;

use App\DataTransfer\Account\Balance\AccountWithdrawPixData;
use App\Model\AccountWithdrawPix;
use App\Repository\Contract\AccountWithdrawPixRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Stringable\Str;
use Throwable;

/**
 * Repositório para dados PIX de saques
 * 
 * Gerencia as operações de persistência dos dados PIX específicos vinculados aos saques,
 * seguindo o padrão estabelecido de retornar apenas DTOs
 */
class AccountWithdrawPixRepository extends BaseRepository implements AccountWithdrawPixRepositoryInterface
{
    public function __construct(private AccountWithdrawPix $accountWithdrawPix = new AccountWithdrawPix())
    {
    }

    /**
     * Retorna o modelo que este repositório gerencia
     * 
     * @return AccountWithdrawPix
     */
    protected function getModel(): AccountWithdrawPix
    {
        return $this->accountWithdrawPix;
    }

    /**
     * {@inheritdoc}
     */
    public function findByWithdrawId(string $withdrawId): ?AccountWithdrawPixData
    {
        /** @var AccountWithdrawPix|null $pixData */
        $pixData = AccountWithdrawPix::query()
            ->where('account_withdraw_id', $withdrawId)
            ->first();

        return $pixData ? AccountWithdrawPixData::fromModel($pixData) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function createPixData(string $withdrawId, string $key, string $type, ?string $externalId = null): AccountWithdrawPixData
    {
        try {
            return $this->transaction(function () use ($withdrawId, $key, $type, $externalId) {
                /** @var AccountWithdrawPix $pixData */
                $pixData = AccountWithdrawPix::query()->create([
                    'id' => (string) Str::uuid(),
                    'account_withdraw_id' => $withdrawId,
                    'external_id' => $externalId,
                    'type' => $type,
                    'key' => $key,
                ]);

                return AccountWithdrawPixData::fromModel($pixData);
            });
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Erro ao criar dados PIX: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Encontra dados PIX pelo ID retornando DTO
     * 
     * @param string $id
     * @return AccountWithdrawPixData|null
     */
    public function findPixById(string $id): ?AccountWithdrawPixData
    {
        /** @var AccountWithdrawPix|null $pixData */
        $pixData = AccountWithdrawPix::query()->find($id);

        return $pixData ? AccountWithdrawPixData::fromModel($pixData) : null;
    }

    /**
     * Encontra dados PIX pelo ID ou lança exceção retornando DTO
     * 
     * @param string $id
     * @return AccountWithdrawPixData
     * @throws RepositoryNotFoundException
     */
    public function findPixByIdOrFail(string $id): AccountWithdrawPixData
    {
        $pixData = $this->findPixById($id);
        
        if (!$pixData) {
            throw new RepositoryNotFoundException("Dados PIX com ID '{$id}' não encontrados.");
        }

        return $pixData;
    }

    /**
     * Verifica se existem dados PIX para um saque
     * 
     * @param string $withdrawId
     * @return bool
     */
    public function existsForWithdraw(string $withdrawId): bool
    {
        return AccountWithdrawPix::query()
            ->where('account_withdraw_id', $withdrawId)
            ->exists();
    }

    /**
     * Busca dados PIX por chave
     * 
     * @param string $key
     * @param string|null $type
     * @return array Lista de AccountWithdrawPixData
     */
    public function findByKey(string $key, ?string $type = null): array
    {
        $query = AccountWithdrawPix::query()->where('key', $key);
        
        if ($type !== null) {
            $query->where('type', $type);
        }

        /** @var \Hyperf\Database\Model\Collection $pixDataCollection */
        $pixDataCollection = $query->get();

        return $pixDataCollection->map(static function ($pixData): AccountWithdrawPixData {
            \assert($pixData instanceof AccountWithdrawPix);
            return AccountWithdrawPixData::fromModel($pixData);
        })->toArray();
    }

    /**
     * Lista todos os dados PIX com paginação
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function paginatePixData(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        /** @var \Hyperf\Database\Model\Collection $pixDataCollection */
        $pixDataCollection = AccountWithdrawPix::query()
            ->offset($offset)
            ->limit($perPage)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = AccountWithdrawPix::query()->count();

        return [
            'data' => $pixDataCollection->map(static function ($pixData): AccountWithdrawPixData {
                \assert($pixData instanceof AccountWithdrawPix);
                return AccountWithdrawPixData::fromModel($pixData);
            })->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
}