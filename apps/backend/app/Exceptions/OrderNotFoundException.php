<?php

namespace App\Exceptions;

use Exception;

class OrderNotFoundException extends Exception
{
    public function __construct(string $message = 'Không tìm thấy đơn hàng', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
