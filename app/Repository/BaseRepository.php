<?php

declare(strict_types=1);

namespace App\Repository;

use App\Repository\Contract\BaseRepositoryInterface;
use App\Repository\Exceptions\RepositoryNotFoundException;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\DbConnection\Db;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Stringable\Str;
use Throwable;

/**
 * Class BaseRepository
 * 
 * Repositório base com funcionalidades genéricas para interação com Models
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Retorna o modelo que este repositório gerencia
     * 
     * @return Model
     */
    abstract protected function getModel(): Model;

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?Model
    {
        /** @var Model|null $model */
        $model = $this->getModel()::query()->find($id);
        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIdOrFail(string $id): Model
    {
        try {
            /** @var Model $model */
            $model = $this->getModel()::query()->findOrFail($id);
            return $model;
        } catch (ModelNotFoundException $e) {
            throw new RepositoryNotFoundException(
                "Registro com ID '{$id}' não encontrado.",
                previous: $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria): Collection
    {
        $query = $this->getModel()::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        /** @var Collection $result */
        $result = $query->get();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria): ?Model
    {
        $query = $this->getModel()::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        /** @var Model|null $model */
        $model = $query->first();
        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): Collection
    {
        return $this->getModel()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        try {
            return $this->transaction(function () use ($data) {
                // Gera UUID se necessário e não fornecido
                if (!isset($data['id']) && $this->getModel()->incrementing === false) {
                    $data['id'] = (string) Str::uuid();
                }

                /** @var Model $model */
                $model = $this->getModel()::query()->create($data);
                return $model;
            });
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Erro ao criar registro: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): bool
    {
        $model = $this->findById($id);

        if (!$model) {
            throw new RepositoryNotFoundException("Registro com ID '{$id}' não encontrado.");
        }

        return $model->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateBy(array $criteria, array $data): int
    {
        $query = $this->getModel()->newQuery();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): bool
    {
        $model = $this->findById($id);

        if ($model === null) {
            throw new RepositoryNotFoundException("Registro com ID '{$id}' não encontrado.");
        }

        $result = $model->delete();
        return $result === true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBy(array $criteria): int
    {
        $query = $this->getModel()->newQuery();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $query = $this->getModel()->newQuery();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $criteria): bool
    {
        $query = $this->getModel()->newQuery();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback): mixed
    {
        return Db::transaction(\Closure::fromCallable($callback));
    }

    /**
     * Aplica paginação à query
     * 
     * @param int $page
     * @param int $perPage
     * @param array $criteria
     * @return \Hyperf\Paginator\LengthAwarePaginator
     */
    public function paginate(int $page = 1, int $perPage = 15, array $criteria = []): \Hyperf\Paginator\LengthAwarePaginator
    {
        $query = $this->getModel()::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        /** @var \Hyperf\Paginator\LengthAwarePaginator $result */
        $result = $query->paginate($perPage, ['*'], 'page', $page);
        return $result;
    }

    /**
     * Aplica ordenação personalizada
     * 
     * @param array $criteria
     * @param array $orderBy ['field' => 'direction']
     * @return Collection
     */
    public function findByWithOrder(array $criteria, array $orderBy): Collection
    {
        $query = $this->getModel()::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        /** @var Collection $result */
        $result = $query->get();
        return $result;
    }
}
