<?php

namespace App\Http\Resources;

use App\Domain\Costs\BudgetCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tarifa histórica de un rol; el monto se expresa también en decimal. */
class RoleRateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'currency' => $this->currency,
            'rate_cents' => (int) $this->rate_cents,
            'rate' => BudgetCalculator::format((int) $this->rate_cents),
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'notes' => $this->notes,
        ];
    }
}
