<?php

namespace App\Http\Requests\Brand;

use App\Http\Requests\BaseApiRequest;

class StoreBrandRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
