<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hora registrada. Expone el snapshot de tarifa del momento (RN-04/RN-11),
 * necesario para los reportes de auditoría de la fase 5.
 */
class TimeEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_date' => $this->work_date,
            'hours' => $this->hours(),
            'minutes' => (int) $this->minutes,
            'rate_cents' => (int) $this->rate_cents,
            'amount_cents' => (int) $this->amount_cents,
            'description' => $this->description,
            'team_member' => $this->whenLoaded('teamMember', fn () => [
                'id' => $this->teamMember->id,
                'name' => $this->teamMember->name,
            ]),
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'code' => $this->role->code,
                'name' => $this->role->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
