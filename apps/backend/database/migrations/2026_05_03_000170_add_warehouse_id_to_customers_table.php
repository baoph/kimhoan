<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'warehouse_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            // Global warehouse context: khách hàng thuộc riêng 1 kho.
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->index('warehouse_id');
        });

        $defaultWarehouseId = DB::table('warehouses')->orderBy('id')->value('id');
        if ($defaultWarehouseId) {
            DB::table('customers')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE customers MODIFY warehouse_id BIGINT UNSIGNED NOT NULL');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'warehouse_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id']);
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
