<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'current_warehouse_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'staff_id');
    }

    public function createdCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function createdPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    /**
     * Nhân viên có thể làm ở nhiều kho (global warehouse context).
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouses')
            ->withTimestamps();
    }

    public function hasAccessToWarehouse(int|string|null $warehouseId): bool
    {
        if (! $warehouseId) {
            return false;
        }

        if ($this->role === 'admin') {
            return true;
        }

        return $this->warehouses()->where('warehouses.id', $warehouseId)->exists();
    }

    /**
     * Thuộc tính động lấy kho hiện tại từ request header/context.
     */
    public function getCurrentWarehouseIdAttribute(): ?int
    {
        if (! app()->bound('request')) {
            return null;
        }

        $currentWarehouseId = request()->input('current_warehouse_id')
            ?? request()->header('X-Warehouse-Id');

        return $currentWarehouseId ? (int) $currentWarehouseId : null;
    }
}
