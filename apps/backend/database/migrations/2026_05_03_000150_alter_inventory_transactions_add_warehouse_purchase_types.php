<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mở rộng enum transaction_type nhưng vẫn giữ các giá trị cũ để tương thích dữ liệu.
     */
    public function up(): void
    {
        if (! Schema::hasTable('inventory_transactions') || ! Schema::hasColumn('inventory_transactions', 'transaction_type')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN transaction_type ENUM('import','export','adjustment','return','sale','sale_return','purchase','purchase_cancel','transfer_out','transfer_in') NOT NULL");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_transactions') || ! Schema::hasColumn('inventory_transactions', 'transaction_type')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::table('inventory_transactions')
                ->whereIn('transaction_type', ['sale', 'sale_return', 'purchase', 'purchase_cancel', 'transfer_out', 'transfer_in'])
                ->update(['transaction_type' => 'adjustment']);

            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN transaction_type ENUM('import','export','adjustment','return') NOT NULL");
        }
    }
};
