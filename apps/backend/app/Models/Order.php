<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'order_code',
        'customer_id',
        'staff_id',
        'order_date',
        'total_amount',
        'discount',
        'final_amount',
        'payment_status',
        'order_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'order_status' => OrderStatus::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('order_status', [OrderStatus::DRAFT, OrderStatus::CONFIRMED]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('order_status', OrderStatus::COMPLETED);
    }

    public function scopeByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeWithFullRelations(Builder $query): Builder
    {
        return $query->with([
            'customer',
            'warehouse',
            'orderItems.product',
            'staff',
        ]);
    }
}
