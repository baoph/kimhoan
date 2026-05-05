<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function createProduct(array $data, ?int $warehouseId = null): Product
    {
        return DB::transaction(function () use ($data, $warehouseId) {
            $product = Product::create($data);

            $initialQuantity = (int) ($data['stock_quantity'] ?? 0);

            Warehouse::query()->select('id')->get()->each(function (Warehouse $warehouse) use ($product, $warehouseId, $initialQuantity) {
                WarehouseStock::query()->firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                ], [
                    'quantity' => $warehouseId === $warehouse->id ? $initialQuantity : 0,
                ]);
            });

            return $product;
        });
    }

    public function updateStock(int $productId, int $warehouseId, int $quantity): WarehouseStock
    {
        $stock = WarehouseStock::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            ['quantity' => 0]
        );

        $stock->update(['quantity' => $quantity]);

        return $stock->fresh(['product', 'warehouse']);
    }

    public function getTotalStock(Product $product): int
    {
        return (int) $product->warehouseStocks()->sum('quantity');
    }
}
