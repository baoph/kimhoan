<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'customer_id') && Schema::hasColumn('orders', 'created_at')) {
                $this->addIndexIfMissing($table, 'orders', ['customer_id', 'created_at'], 'orders_customer_created_at_idx');
            }

            $statusColumn = Schema::hasColumn('orders', 'status')
                ? 'status'
                : (Schema::hasColumn('orders', 'order_status') ? 'order_status' : null);

            if (Schema::hasColumn('orders', 'warehouse_id') && $statusColumn) {
                $this->addIndexIfMissing($table, 'orders', ['warehouse_id', $statusColumn], 'orders_warehouse_status_idx');
            }

            if ($statusColumn) {
                $this->addIndexIfMissing($table, 'orders', $statusColumn, 'orders_status_idx');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'order_id') && Schema::hasColumn('order_items', 'product_id')) {
                $this->addIndexIfMissing($table, 'order_items', ['order_id', 'product_id'], 'order_items_order_product_idx');
            }
        });

        Schema::table('warehouse_stock', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_stock', 'warehouse_id') && Schema::hasColumn('warehouse_stock', 'product_id')) {
                $this->addIndexIfMissing($table, 'warehouse_stock', ['warehouse_id', 'product_id'], 'warehouse_stock_warehouse_product_idx');
                $this->addIndexIfMissing($table, 'warehouse_stock', 'product_id', 'warehouse_stock_product_idx');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sku')) {
                $this->addIndexIfMissing($table, 'products', 'sku', 'products_sku_idx');
            }

            if (Schema::hasColumn('products', 'product_code')) {
                $this->addIndexIfMissing($table, 'products', 'product_code', 'products_product_code_idx');
            }

            if (Schema::hasColumn('products', 'category_id')) {
                $this->addIndexIfMissing($table, 'products', 'category_id', 'products_category_id_idx');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'email')) {
                $this->addIndexIfMissing($table, 'customers', 'email', 'customers_email_idx');
            }

            if (Schema::hasColumn('customers', 'phone')) {
                $this->addIndexIfMissing($table, 'customers', 'phone', 'customers_phone_idx');
            } elseif (Schema::hasColumn('customers', 'phone1')) {
                $this->addIndexIfMissing($table, 'customers', 'phone1', 'customers_phone1_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'orders', 'orders_customer_created_at_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_warehouse_status_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_status_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'order_items', 'order_items_order_product_idx');
        });

        Schema::table('warehouse_stock', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'warehouse_stock', 'warehouse_stock_warehouse_product_idx');
            $this->dropIndexIfExists($table, 'warehouse_stock', 'warehouse_stock_product_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'products', 'products_sku_idx');
            $this->dropIndexIfExists($table, 'products', 'products_product_code_idx');
            $this->dropIndexIfExists($table, 'products', 'products_category_id_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'customers', 'customers_email_idx');
            $this->dropIndexIfExists($table, 'customers', 'customers_phone_idx');
            $this->dropIndexIfExists($table, 'customers', 'customers_phone1_idx');
        });
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array|string $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        $table->index($columns, $indexName);
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            return;
        }

        $table->dropIndex($indexName);
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = DB::getDatabaseName();
            $result = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $tableName, $indexName]
            );

            return ! empty($result);
        }

        if ($driver === 'sqlite') {
            $result = DB::select("PRAGMA index_list('{$tableName}')");

            foreach ($result as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
