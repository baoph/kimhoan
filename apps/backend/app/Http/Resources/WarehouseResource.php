<?php

namespace App\Http\Resources;

use App\Enums\WarehouseStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = WarehouseStatus::fromBool((bool) $this->is_active);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'manager_name' => $this->manager_name,
            'is_active' => (bool) $this->is_active,
            'status' => $status->value,
            'status_label' => $status->label(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
