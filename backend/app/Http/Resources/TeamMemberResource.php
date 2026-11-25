<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Miembro del equipo de un proyecto (RN-03). */
class TeamMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'code' => $this->role->code,
                'name' => $this->role->name,
            ]),
            'role_id' => $this->role_id,
            'active' => (bool) $this->active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
