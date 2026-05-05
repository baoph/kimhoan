<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = 'Số lượng tồn kho không đủ', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
