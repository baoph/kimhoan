<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseApiRequest;

class StoreCategoryRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
