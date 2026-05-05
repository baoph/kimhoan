<?php

namespace App\Enums;

enum WarehouseStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Đang hoạt động',
            self::INACTIVE => 'Ngừng hoạt động',
        };
    }

    public function toBool(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function fromBool(bool $isActive): self
    {
        return $isActive ? self::ACTIVE : self::INACTIVE;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
