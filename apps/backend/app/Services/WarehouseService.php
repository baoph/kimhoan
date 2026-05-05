<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Repositories\WarehouseRepository;
use App\Repositories\WarehouseStockRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
        private readonly WarehouseStockRepository $stockRepository
    ) {}

    public function createWarehouse(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            /** @var Warehouse $warehouse */
            $warehouse = $this->warehouseRepository->create($data);

            return $warehouse;
        });
    }

    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            /** @var Warehouse $updated */
            $updated = $this->warehouseRepository->update($warehouse, $data);

            return $updated;
        });
    }

    public function getWarehouseStock(int $warehouseId): Collection
    {
        return $this->stockRepository->getByWarehouse($warehouseId, ['product', 'warehouse']);
    }

    public function getLowStockItems(int $warehouseId, int $threshold = 10): Collection
    {
        return $this->stockRepository->getLowStock($warehouseId, $threshold, ['product', 'warehouse']);
    }

    public function getWarehouseInventoryValue(int $warehouseId): float
    {
        $stocks = $this->stockRepository->getByWarehouse($warehouseId, ['product']);

        return (float) $stocks->sum(function ($stock) {
            return $stock->quantity * ($stock->product->selling_price ?? 0);
        });
    }
}
