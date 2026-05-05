<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Quản trị viên',
            self::MANAGER => 'Quản lý',
            self::STAFF => 'Nhân viên',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => ['*'],
            self::MANAGER => ['manage_warehouse', 'manage_orders', 'view_reports'],
            self::STAFF => ['create_orders', 'view_products'],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
