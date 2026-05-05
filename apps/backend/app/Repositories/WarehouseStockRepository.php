<?php

namespace App\Repositories;

use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new WarehouseStock();
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function getByWarehouseAndProducts(int $warehouseId, array $productIds): Collection
    {
        return $this->query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->lockForUpdate()
            ->get();
    }

    public function getByWarehouse(int $warehouseId, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('quantity')
            ->get();
    }

    public function getLowStock(int $warehouseId, int $threshold = 10, array $relations = []): Collection
    {
        return $this->query()
            ->with($relations)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '<=', $threshold)
            ->orderBy('quantity')
            ->get();
    }

    public function getByProductAndWarehouse(int $productId, int $warehouseId): ?WarehouseStock
    {
        return $this->query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    public function firstOrCreateForProduct(int $warehouseId, int $productId, int $defaultQty = 0): WarehouseStock
    {
        return $this->query()->firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ],
            ['quantity' => $defaultQty]
        );
    }
}
