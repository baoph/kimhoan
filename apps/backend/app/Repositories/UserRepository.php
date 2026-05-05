<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new User();
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function getActiveUsers(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    public function getByWarehouse(int $warehouseId): Collection
    {
        return $this->query()
            ->with('warehouses')
            ->whereHas('warehouses', fn (Builder $query) => $query->where('warehouses.id', $warehouseId))
            ->get();
    }

    public function getByRole(UserRole $role): Collection
    {
        return $this->query()
            ->with('warehouses')
            ->where('role', $role->value)
            ->get();
    }
}
