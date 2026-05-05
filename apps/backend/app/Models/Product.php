<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'barcode',
        'name',
        'category_id',
        'brand_id',
        'cost_price',
        'selling_price',
        'stock_quantity',
        'min_stock',
        'max_stock',
        'unit',
        'weight',
        'description',
        'images',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'images' => 'array',
            'status' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $searchQuery) use ($term): void {
            $searchQuery->where('name', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhere('product_code', 'like', "%{$term}%");
        });
    }

    public function scopeLowStock(Builder $query, int $threshold = 10): Builder
    {
        return $query->whereHas('warehouseStocks', function (Builder $stockQuery) use ($threshold): void {
            $stockQuery->where('quantity', '<', $threshold);
        });
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::make(get: fn () => $this->stock_quantity <= $this->min_stock);
    }
}
