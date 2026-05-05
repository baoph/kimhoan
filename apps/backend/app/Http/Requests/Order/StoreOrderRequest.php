<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'order_code' => ['required', 'string', 'max:50', 'unique:orders,order_code'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'staff_id' => ['nullable', 'exists:users,id'],
            'order_date' => ['required', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', Rule::in(PaymentStatus::values())],
            'order_status' => ['nullable', Rule::in(OrderStatus::values())],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
