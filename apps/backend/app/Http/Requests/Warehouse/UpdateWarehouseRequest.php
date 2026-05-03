<?php

namespace App\Http\Requests\Warehouse;

use App\Http\Requests\BaseApiRequest;

class UpdateWarehouseRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:warehouses,code,'.$warehouseId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã kho',
            'name' => 'tên kho',
            'address' => 'địa chỉ',
            'phone' => 'số điện thoại',
            'manager_name' => 'người quản lý',
            'is_active' => 'trạng thái hoạt động',
        ];
    }
}
