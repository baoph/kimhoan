<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventory_transactions', 'warehouse_id')) {
            return;
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->index('warehouse_id');
        });

        $defaultWarehouseId = DB::table('warehouses')->orderBy('id')->value('id');
        if (! $defaultWarehouseId) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // Ưu tiên backfill theo đơn bán hàng (orders) nếu tham chiếu có tồn tại.
            DB::statement(
                'UPDATE inventory_transactions it '
                .'INNER JOIN orders o ON o.id = it.reference_id '
                .'SET it.warehouse_id = o.warehouse_id '
                .'WHERE it.warehouse_id IS NULL'
            );

            // Ưu tiên backfill theo phiếu nhập (purchase_orders) nếu tham chiếu có tồn tại.
            DB::statement(
                'UPDATE inventory_transactions it '
                .'INNER JOIN purchase_orders po ON po.id = it.reference_id '
                .'SET it.warehouse_id = po.warehouse_id '
                .'WHERE it.warehouse_id IS NULL'
            );
        }

        // Fallback: gán kho mặc định (kho đầu tiên).
        DB::table('inventory_transactions')
            ->whereNull('warehouse_id')
            ->update(['warehouse_id' => $defaultWarehouseId]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inventory_transactions MODIFY warehouse_id BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('inventory_transactions', 'warehouse_id')) {
            return;
        }

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id']);
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
