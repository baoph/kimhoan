<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Order();
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function getByWarehouse(int $warehouseId, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->byWarehouse($warehouseId)
            ->latest('order_date')
            ->get();
    }

    public function getByCustomer(int $customerId, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->byCustomer($customerId)
            ->latest('order_date')
            ->get();
    }

    public function getByStatus(OrderStatus $status, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->where('order_status', $status)
            ->latest('order_date')
            ->get();
    }

    public function getTodayOrders(int $warehouseId): Collection
    {
        return $this->query()
            ->byWarehouse($warehouseId)
            ->where('order_date', '>=', now()->startOfDay())
            ->get();
    }

    public function getPendingOrders(int $warehouseId): Collection
    {
        return $this->query()
            ->with(['customer', 'orderItems.product'])
            ->byWarehouse($warehouseId)
            ->whereIn('order_status', [OrderStatus::DRAFT, OrderStatus::CONFIRMED])
            ->latest('order_date')
            ->get();
    }

    public function getPaginatedByWarehouse(int $warehouseId, int $perPage = 15, array $relations = [])
    {
        return $this->query()
            ->with($relations)
            ->byWarehouse($warehouseId)
            ->latest('order_date')
            ->paginate($perPage);
    }
}
