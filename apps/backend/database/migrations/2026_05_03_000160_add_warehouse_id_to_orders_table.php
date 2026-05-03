<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'warehouse_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            // Thêm nullable trước để tránh lỗi với dữ liệu cũ, sau đó sẽ backfill và ép NOT NULL.
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->index('warehouse_id');
        });

        $defaultWarehouseId = DB::table('warehouses')->orderBy('id')->value('id');
        if ($defaultWarehouseId) {
            DB::table('orders')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);

            // Chỉ MySQL/MariaDB mới hỗ trợ MODIFY như dưới.
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE orders MODIFY warehouse_id BIGINT UNSIGNED NOT NULL');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'warehouse_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id']);
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
