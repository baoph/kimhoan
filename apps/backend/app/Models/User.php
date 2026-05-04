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
        'is_active',
        'last_login_at',
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
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

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
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

        if ($this->isAdmin()) {
            return true;
        }

        return $this->warehouses()->where('warehouses.id', $warehouseId)->exists();
    }

    public function canAccessWarehouse(int|string|null $warehouseId): bool
    {
        return $this->hasAccessToWarehouse($warehouseId);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function lock(): void
    {
        $this->update(['is_active' => false]);
    }

    public function unlock(): void
    {
        $this->update(['is_active' => true]);
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
