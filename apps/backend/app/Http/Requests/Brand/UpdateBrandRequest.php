<?php

namespace App\Http\Requests\Brand;

use App\Http\Requests\BaseApiRequest;

class UpdateBrandRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $brandId = $this->route('brand')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:brands,name,'.$brandId],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
