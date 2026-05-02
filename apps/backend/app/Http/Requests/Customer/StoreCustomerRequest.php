<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseApiRequest;

class StoreCustomerRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'customer_code' => ['required', 'string', 'max:50', 'unique:customers,customer_code'],
            'name' => ['required', 'string', 'max:255'],
            'phone1' => ['nullable', 'string', 'max:20'],
            'phone2' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
