<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CustomerRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Customer();
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function getByWarehouse(int $warehouseId, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->where('warehouse_id', $warehouseId)
            ->latest()
            ->get();
    }

    public function searchByWarehouse(int $warehouseId, string $term, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->where('warehouse_id', $warehouseId)
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('customer_code', 'like', "%{$term}%")
                    ->orWhere('phone1', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->latest()
            ->get();
    }

    public function search(string $term, ?int $warehouseId = null, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->when($warehouseId, fn (Builder $query) => $query->where('warehouse_id', $warehouseId))
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('customer_code', 'like', "%{$term}%")
                    ->orWhere('phone1', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->latest()
            ->get();
    }

    public function getTopCustomers(int $limit = 10, ?int $warehouseId = null): Collection
    {
        return $this->query()
            ->when($warehouseId, fn (Builder $query) => $query->where('warehouse_id', $warehouseId))
            ->withCount('orders')
            ->withSum('orders as total_spent', 'final_amount')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }
}
