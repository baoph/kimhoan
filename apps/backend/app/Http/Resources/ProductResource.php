<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'stock_quantity' => (int) $this->stock_quantity,
            'current_stock_quantity' => (int) ($this->current_stock_quantity ?? $this->stock_quantity ?? 0),
            'min_stock' => (int) ($this->min_stock ?? 0),
            'max_stock' => (int) ($this->max_stock ?? 0),
            'unit' => $this->unit,
            'weight' => $this->weight,
            'description' => $this->description,
            'images' => $this->images,
            'status' => (bool) $this->status,
            'category' => $this->whenLoaded('category'),
            'brand' => $this->whenLoaded('brand'),
            'warehouse_stocks' => $this->whenLoaded('warehouseStocks'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
