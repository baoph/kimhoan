<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $groupIds = CustomerGroup::query()->pluck('id')->all();
        $staffId = User::query()->where('role', 'staff')->value('id')
            ?? User::query()->value('id');

        $customers = [
            ['customer_code' => 'KH000022', 'name' => 'VĂN NGÂN,HÀ NỘI', 'phone1' => '0912000022'],
            ['customer_code' => 'KH000021', 'name' => 'anh Phúc,cai lậy', 'phone1' => '0912000021'],
            ['customer_code' => 'KH000020', 'name' => 'GIA PHAT, HỘT ĐÁ', 'phone1' => '0912000020'],
            ['customer_code' => 'KH000019', 'name' => 'ĐỨC ,Bà Rịa', 'phone1' => '0912000019'],
            ['customer_code' => 'KH000018', 'name' => 'NA LY BÉ CHO', 'phone1' => '0912000018'],
            ['customer_code' => 'KH000017', 'name' => 'Tiệm Vàng Kim Thúy', 'phone1' => '0912000017'],
            ['customer_code' => 'KH000016', 'name' => 'ALLOY', 'phone1' => '0912000016'],
            ['customer_code' => 'KH000015', 'name' => 'HUY TÙNG', 'phone1' => '0912000015'],
            ['customer_code' => 'KH000014', 'name' => 'CHỊ THỦY,(A THÀNH,H HỘI', 'phone1' => '0912000014'],
            ['customer_code' => 'KH000013', 'name' => 'HUY TUNG', 'phone1' => '0982004486'],
        ];

        foreach ($customers as $index => $customer) {
            Customer::query()->updateOrCreate(
                ['customer_code' => $customer['customer_code']],
                $customer + [
                    'phone2' => null,
                    'email' => 'khach'.($index + 1).'@example.com',
                    'facebook' => null,
                    'address' => 'Địa chỉ mẫu '.($index + 1),
                    'district' => 'Quận/Huyện mẫu',
                    'ward' => 'Phường/Xã mẫu',
                    'gender' => $index % 2 === 0 ? 'male' : 'female',
                    'birth_date' => now()->subYears(25 + $index)->toDateString(),
                    'customer_group_id' => $groupIds[$index % max(count($groupIds), 1)] ?? null,
                    'notes' => null,
                    'created_by' => $staffId,
                ]
            );
        }
    }
}
