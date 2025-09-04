<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Model
 */
interface BaseRepositoryInterface
{
    /**
     * @return Collection|T[]
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;
    
    /**
     * @return T|Model|null
     */
    public function findById(int $modelId, array $columns = ['*'], array $relations = []);
    
    /**
     * @param array $payload
     * @return T|Model|null
     */
    public function create(array $payload);
    
    public function update(int $modelId, array $payload): bool;
    
    public function deleteById(int $modelId): bool;
    
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []);
    
    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(\Closure $callback, int $attempts = 1);
}
