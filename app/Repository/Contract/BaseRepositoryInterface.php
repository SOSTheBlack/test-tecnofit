<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Model\Model;

/**
 * Interface BaseRepositoryInterface
 *
 * Interface genérica para funcionalidades básicas de repositório
 */
interface BaseRepositoryInterface
{
    /**
     * Encontra um registro pelo ID
     *
     * @param string $id
     * @return Model|null
     * @throws RepositoryNotFoundException
     */
    public function findById(string $id): ?Model;

    /**
     * Encontra um registro pelo ID ou falha
     *
     * @param string $id
     * @return Model
     * @throws RepositoryNotFoundException
     */
    public function findByIdOrFail(string $id): Model;

    /**
     * Encontra registros por critérios
     *
     * @param array $criteria
     * @return Collection
     */
    public function findBy(array $criteria): Collection;

    /**
     * Encontra um registro por critérios
     *
     * @param array $criteria
     * @return Model|null
     */
    public function findOneBy(array $criteria): ?Model;

    /**
     * Lista todos os registros
     *
     * @return Collection
     */
    public function findAll(): Collection;

    /**
     * Cria um novo registro
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Atualiza um registro pelo ID
     *
     * @param string $id
     * @param array $data
     * @return bool
     * @throws RepositoryNotFoundException
     */
    public function update(string $id, array $data): bool;

    /**
     * Atualiza registros por critérios
     *
     * @param array $criteria
     * @param array $data
     * @return int Número de registros atualizados
     */
    public function updateBy(array $criteria, array $data): int;

    /**
     * Deleta um registro pelo ID
     *
     * @param string $id
     * @return bool
     * @throws RepositoryNotFoundException
     */
    public function delete(string $id): bool;

    /**
     * Deleta registros por critérios
     *
     * @param array $criteria
     * @return int Número de registros deletados
     */
    public function deleteBy(array $criteria): int;

    /**
     * Conta registros por critérios
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * Verifica se existe registro com os critérios
     *
     * @param array $criteria
     * @return bool
     */
    public function exists(array $criteria): bool;

    /**
     * Executa transação
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed;
}
