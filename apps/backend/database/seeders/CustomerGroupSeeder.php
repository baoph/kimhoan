<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Khách lẻ', 'description' => 'Khách mua tự do'],
            ['name' => 'Khách sỉ', 'description' => 'Khách mua số lượng lớn'],
            ['name' => 'Khách VIP', 'description' => 'Khách hàng thân thiết'],
        ];

        foreach ($groups as $group) {
            CustomerGroup::query()->updateOrCreate(['name' => $group['name']], $group);
        }
    }
}
