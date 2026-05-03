<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_code' => 'NCC-0001',
                'name' => 'Công ty TNHH ABC',
                'contact_person' => 'Nguyễn Văn A',
                'phone' => '0901000001',
                'email' => 'abc@nhacungcap.vn',
                'address' => 'KCN Quang Minh, Hà Nội',
                'tax_code' => '0101234567',
                'notes' => 'Nhà cung cấp vật tư chính',
                'is_active' => true,
            ],
            [
                'supplier_code' => 'NCC-0002',
                'name' => 'NCC XYZ',
                'contact_person' => 'Trần Thị B',
                'phone' => '0901000002',
                'email' => 'xyz@nhacungcap.vn',
                'address' => 'Long Biên, Hà Nội',
                'tax_code' => '0107654321',
                'notes' => 'Nhà cung cấp bao bì',
                'is_active' => true,
            ],
            [
                'supplier_code' => 'NCC-0003',
                'name' => 'Công ty Vật tư Minh Phát',
                'contact_person' => 'Lê Văn C',
                'phone' => '0901000003',
                'email' => 'minhphat@nhacungcap.vn',
                'address' => 'Hoàng Mai, Hà Nội',
                'tax_code' => '0101111222',
                'notes' => 'Nhà cung cấp dự phòng',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(['supplier_code' => $supplier['supplier_code']], $supplier);
        }
    }
}
