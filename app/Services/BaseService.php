<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    protected $repository;

    public function all(array $columns = ['*'], array $relations = [])
    {
        try {
            return $this->repository->all($columns, $relations);
        } catch (\Exception $e) {
            Log::error('Error in ' . __METHOD__ . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function findById(int $id, array $columns = ['*'], array $relations = [])
    {
        try {
            return $this->repository->findById($id, $columns, $relations);
        } catch (\Exception $e) {
            Log::error('Error in ' . __METHOD__ . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            return $this->repository->create($data);
        } catch (\Exception $e) {
            Log::error('Error in ' . __METHOD__ . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            return $this->repository->update($id, $data);
        } catch (\Exception $e) {
            Log::error('Error in ' . __METHOD__ . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return $this->repository->deleteById($id);
        } catch (\Exception $e) {
            Log::error('Error in ' . __METHOD__ . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = [])
    {
        try {
            return $this->repository->paginate($perPage, $columns, $relations);
        } catch (\Exception $e) {
            Log::error('Error in ' . __METHOD__ . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
