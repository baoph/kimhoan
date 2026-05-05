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
