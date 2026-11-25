<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Rol de trabajo del catálogo (RN-04). */
class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'active' => (bool) $this->active,
            'rates' => $this->whenLoaded('rates', fn () => RoleRateResource::collection($this->rates)),
        ];
    }
}
