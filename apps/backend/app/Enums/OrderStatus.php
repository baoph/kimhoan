<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Nháp',
            self::CONFIRMED => 'Đã xác nhận',
            self::COMPLETED => 'Hoàn thành',
            self::CANCELLED => 'Đã hủy',
            self::RETURNED => 'Đã trả hàng',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
