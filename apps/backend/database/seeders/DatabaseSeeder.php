<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CustomerGroupSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
            WarehouseSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            PurchaseOrderSeeder::class,
            UserWarehouseSeeder::class,
        ]);
    }
}
