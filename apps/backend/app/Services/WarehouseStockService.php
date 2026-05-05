<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use App\Models\WarehouseStock;
use App\Repositories\WarehouseStockRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseStockService
{
    public function __construct(
        private readonly WarehouseStockRepository $stockRepository
    ) {}

    public function adjustStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?string $notes = null
    ): WarehouseStock {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $notes) {
            $stock = $this->stockRepository->getByProductAndWarehouse($productId, $warehouseId);

            if (! $stock) {
                throw new RuntimeException('Không tìm thấy tồn kho cho sản phẩm tại kho đã chọn');
            }

            $oldQuantity = (int) $stock->quantity;
            $newQuantity = $oldQuantity + $quantity;

            if ($newQuantity < 0) {
                throw new RuntimeException('Số lượng tồn không đủ để điều chỉnh');
            }

            /** @var WarehouseStock $updatedStock */
            $updatedStock = $this->stockRepository->update($stock, ['quantity' => $newQuantity]);

            InventoryTransaction::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => InventoryTransactionType::ADJUSTMENT,
                'quantity' => $quantity,
                'reference_type' => 'manual_adjustment',
                'notes' => $notes ?? "Điều chỉnh tồn kho: {$oldQuantity} -> {$newQuantity}",
            ]);

            return $updatedStock;
        });
    }

    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $notes) {
            if ($quantity <= 0) {
                throw new RuntimeException('Số lượng chuyển kho phải lớn hơn 0');
            }

            if ($fromWarehouseId === $toWarehouseId) {
                throw new RuntimeException('Kho nguồn và kho đích không được trùng nhau');
            }

            $fromStock = $this->stockRepository->getByProductAndWarehouse($productId, $fromWarehouseId);

            if (! $fromStock || $fromStock->quantity < $quantity) {
                throw new RuntimeException('Không đủ tồn kho tại kho nguồn');
            }

            $this->stockRepository->update($fromStock, [
                'quantity' => (int) $fromStock->quantity - $quantity,
            ]);

            $toStock = $this->stockRepository->firstOrCreateForProduct($toWarehouseId, $productId, 0);
            $this->stockRepository->update($toStock, [
                'quantity' => (int) $toStock->quantity + $quantity,
            ]);

            InventoryTransaction::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $fromWarehouseId,
                'transaction_type' => InventoryTransactionType::TRANSFER_OUT,
                'quantity' => -$quantity,
                'reference_type' => 'transfer',
                'notes' => $notes ?? "Chuyển kho sang #{$toWarehouseId}",
            ]);

            InventoryTransaction::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'transaction_type' => InventoryTransactionType::TRANSFER_IN,
                'quantity' => $quantity,
                'reference_type' => 'transfer',
                'notes' => $notes ?? "Nhận chuyển kho từ #{$fromWarehouseId}",
            ]);

            return [
                'from' => $fromStock->fresh(),
                'to' => $toStock->fresh(),
            ];
        });
    }
}
