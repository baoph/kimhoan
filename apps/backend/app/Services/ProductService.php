<?php

namespace App\Services;

use App\Events\StockUpdated;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Repositories\ProductRepository;
use App\Repositories\WarehouseStockRepository;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly WarehouseStockRepository $warehouseStockRepository,
    ) {}

    public function createProduct(array $data, ?int $warehouseId = null): Product
    {
        return DB::transaction(function () use ($data, $warehouseId) {
            /** @var Product $product */
            $product = $this->productRepository->create($data);

            $initialQuantity = (int) ($data['stock_quantity'] ?? 0);

            Warehouse::query()->select('id')->get()->each(function (Warehouse $warehouse) use ($product, $warehouseId, $initialQuantity): void {
                $quantity = $warehouseId === $warehouse->id ? $initialQuantity : 0;

                $this->warehouseStockRepository->firstOrCreateForProduct(
                    $warehouse->id,
                    $product->id,
                    $quantity
                );
            });

            return $product;
        });
    }

    public function updateStock(int $productId, int $warehouseId, int $quantity): WarehouseStock
    {
        $stock = $this->warehouseStockRepository->firstOrCreateForProduct($warehouseId, $productId, 0);

        $beforeQty = (int) $stock->quantity;
        $stock->update(['quantity' => $quantity]);

        event(new StockUpdated(
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity - $beforeQty,
            currentStock: $quantity,
        ));

        return $stock->fresh(['product', 'warehouse']);
    }

    public function getTotalStock(Product $product): int
    {
        return (int) $product->warehouseStocks()->sum('quantity');
    }
}
