<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseApiRequest;

class UpdateProductRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'product_code' => ['sometimes', 'required', 'string', 'max:50', 'unique:products,product_code,'.$productId],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,'.$productId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'cost_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
