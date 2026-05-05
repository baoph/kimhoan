<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case IMPORT = 'import';
    case EXPORT = 'export';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';
    case SALE = 'sale';
    case SALE_RETURN = 'sale_return';
    case PURCHASE = 'purchase';
    case PURCHASE_CANCEL = 'purchase_cancel';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
