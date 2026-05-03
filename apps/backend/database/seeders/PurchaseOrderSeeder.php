<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::query()->where('code', 'KHO-TT')->first() ?? Warehouse::query()->first();
        $supplierA = Supplier::query()->where('supplier_code', 'NCC-0001')->first() ?? Supplier::query()->first();
        $supplierB = Supplier::query()->where('supplier_code', 'NCC-0002')->first() ?? Supplier::query()->skip(1)->first();
        $creatorId = User::query()->where('role', 'admin')->value('id') ?? User::query()->value('id');
        $products = Product::query()->take(4)->get();

        if (! $warehouse || ! $supplierA || ! $supplierB || ! $creatorId || $products->count() < 2) {
            return;
        }

        $orders = [
            [
                'po_code' => 'PN-20260503-0001',
                'supplier_id' => $supplierA->id,
                'order_date' => '2026-05-03',
                'notes' => 'Phiếu nhập mẫu #1',
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 20, 'unit_price' => (float) $products[0]->cost_price],
                    ['product_id' => $products[1]->id, 'quantity' => 10, 'unit_price' => (float) $products[1]->cost_price],
                ],
            ],
            [
                'po_code' => 'PN-20260503-0002',
                'supplier_id' => $supplierB->id,
                'order_date' => '2026-05-03',
                'notes' => 'Phiếu nhập mẫu #2',
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 5, 'unit_price' => (float) $products[0]->cost_price],
                    ['product_id' => $products[1]->id, 'quantity' => 8, 'unit_price' => (float) $products[1]->cost_price],
                ],
            ],
        ];

        foreach ($orders as $seedOrder) {
            if (PurchaseOrder::query()->where('po_code', $seedOrder['po_code'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($seedOrder, $warehouse, $creatorId) {
                $totalAmount = collect($seedOrder['items'])
                    ->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

                $purchaseOrder = PurchaseOrder::create([
                    'po_code' => $seedOrder['po_code'],
                    'warehouse_id' => $warehouse->id,
                    'supplier_id' => $seedOrder['supplier_id'],
                    'order_date' => $seedOrder['order_date'],
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                    'notes' => $seedOrder['notes'],
                    'created_by' => $creatorId,
                ]);

                foreach ($seedOrder['items'] as $item) {
                    $purchaseOrder->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                    ]);

                    $stock = WarehouseStock::query()->firstOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'product_id' => $item['product_id'],
                        ],
                        ['quantity' => 0]
                    );
                    $stock->increment('quantity', $item['quantity']);

                    Product::query()->whereKey($item['product_id'])->increment('stock_quantity', $item['quantity']);

                    InventoryTransaction::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $item['product_id'],
                        'transaction_type' => 'purchase',
                        'quantity' => $item['quantity'],
                        'reference_id' => $purchaseOrder->id,
                        'notes' => 'Nhập hàng từ seeder: '.$purchaseOrder->po_code,
                    ]);
                }
            });
        }
    }
}
