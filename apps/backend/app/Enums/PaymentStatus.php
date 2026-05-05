<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ thanh toán',
            self::PAID => 'Đã thanh toán',
            self::PARTIAL => 'Thanh toán một phần',
            self::REFUNDED => 'Đã hoàn tiền',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
