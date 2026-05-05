<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_transactions', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('quantity');
            }

            $table->index(['product_id', 'warehouse_id', 'created_at'], 'inv_tx_product_warehouse_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->dropIndex('inv_tx_product_warehouse_created_idx');

            if (Schema::hasColumn('inventory_transactions', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
        });
    }
};
