<?php

namespace App\Repositories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class WarehouseRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Warehouse();
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function getActive(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->query()
            ->whereHas('users', fn (Builder $query) => $query->where('users.id', $userId))
            ->get();
    }
}
