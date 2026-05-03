<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\BaseApiRequest;

class UpdateSupplierRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;

        return [
            'supplier_code' => ['sometimes', 'required', 'string', 'max:50', 'unique:suppliers,supplier_code,'.$supplierId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_code' => 'mã nhà cung cấp',
            'name' => 'tên nhà cung cấp',
            'contact_person' => 'người liên hệ',
            'phone' => 'số điện thoại',
            'email' => 'email',
            'address' => 'địa chỉ',
            'tax_code' => 'mã số thuế',
            'notes' => 'ghi chú',
            'is_active' => 'trạng thái hoạt động',
        ];
    }
}
