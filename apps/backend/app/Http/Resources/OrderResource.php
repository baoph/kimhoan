<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'order_number' => $this->order_code ?? $this->id,
            'warehouse_id' => $this->warehouse_id,
            'customer_id' => $this->customer_id,
            'staff_id' => $this->staff_id,
            'order_date' => $this->order_date?->toISOString(),
            'order_status' => $this->order_status?->value,
            'order_status_label' => $this->order_status?->label(),
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status?->label(),
            'total_amount' => $this->total_amount,
            'discount' => $this->discount,
            'final_amount' => $this->final_amount,
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'staff' => new UserResource($this->whenLoaded('staff')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
