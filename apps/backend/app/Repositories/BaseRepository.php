<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    abstract protected function getModel(): Model;

    public function all(array $relations = []): Collection
    {
        return $this->model->newQuery()->with($relations)->get();
    }

    public function find(int $id, array $relations = []): ?Model
    {
        return $this->model->newQuery()->with($relations)->find($id);
    }

    public function findOrFail(int $id, array $relations = []): Model
    {
        return $this->model->newQuery()->with($relations)->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function paginate(int $perPage = 15, array $relations = [])
    {
        return $this->model->newQuery()->with($relations)->paginate($perPage);
    }
}
