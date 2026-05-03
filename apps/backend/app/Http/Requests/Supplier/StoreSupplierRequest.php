<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\BaseApiRequest;
use App\Models\Supplier;

class StoreSupplierRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('supplier_code')) {
            $lastCode = Supplier::query()->latest('id')->value('supplier_code');
            $lastNumber = (int) preg_replace('/\D/', '', (string) $lastCode);

            $this->merge([
                'supplier_code' => 'NCC-'.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'supplier_code' => ['required', 'string', 'max:50', 'unique:suppliers,supplier_code'],
            'name' => ['required', 'string', 'max:255'],
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
