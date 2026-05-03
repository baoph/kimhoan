<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'customer_code',
        'name',
        'phone1',
        'phone2',
        'email',
        'facebook',
        'address',
        'district',
        'ward',
        'gender',
        'birth_date',
        'customer_group_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return collect([$this->address, $this->ward, $this->district])
                    ->filter()
                    ->implode(', ');
            }
        );
    }
}
