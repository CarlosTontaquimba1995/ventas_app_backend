<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * @template T of Model
 * @implements BaseRepositoryInterface<T>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var T|Model
     */
    protected $model;

    /**
     * @param T $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Collection|T[]
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * @return T|Model|null
     */
    public function findById(int $modelId, array $columns = ['*'], array $relations = [])
    {
        return $this->model->with($relations)->select($columns)->find($modelId);
    }

    /**
     * @return T|Model|null
     */
    public function create(array $payload)
    {
        try {
            DB::beginTransaction();
            $model = $this->model->create($payload);
            DB::commit();
            return $model->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $modelId, array $payload): bool
    {
        $model = $this->findById($modelId);
        
        if (!$model) {
            return false;
        }

        return $model->update($payload);
    }

    public function deleteById(int $modelId): bool
    {
        return $this->findById($modelId)?->delete() ?? false;
    }
    
    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(\Closure $callback, int $attempts = 1)
    {
        return DB::transaction($callback, $attempts);
    }

    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = [])
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * @return T|Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return Builder<T>
     */
    public function getQuery(): Builder
    {
        return $this->model->query();
    }
}
