<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $orderId = $this->route('order')?->id;

        return [
            'order_code' => ['sometimes', 'required', 'string', 'max:50', 'unique:orders,order_code,'.$orderId],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'staff_id' => ['nullable', 'exists:users,id'],
            'order_date' => ['sometimes', 'required', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', Rule::in(PaymentStatus::values())],
            'order_status' => ['nullable', Rule::in(OrderStatus::values())],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
