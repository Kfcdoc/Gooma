<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proyecto con vista sensible al rol (RN-21).
 *
 * El cliente solo recibe montos agregados; el desglose de tarifas, extras y
 * horas individuales se oculta salvo que el gestor lo habilite en el proyecto.
 */
class ProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $totals = $this->actualTotals();
        $clientWithoutDetails = $request->user()?->isCliente() === true
            && ! (bool) $this->show_rates_to_client;

        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'base_currency' => $this->base_currency,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ]),
            'totals' => [
                'planned_total_cents' => $this->budget['total_cents'] ?? $this->budget_initial_cents ?? 0,
                'actual_base_cost_cents' => $totals['base_cost_cents'],
                'total_minutes' => $totals['minutes'],
                'total_hours' => $totals['hours'],
                'time_entries' => $totals['entries'],
            ],
            'technologies' => $this->whenLoaded('projectTechnologies', fn () => $this->projectTechnologies
                ->map(fn ($pivot) => [
                    'id' => $pivot->technology?->id,
                    'name' => $pivot->technology?->name,
                    'version' => $pivot->version,
                ])
                ->values()
                ->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($clientWithoutDetails) {
            return $data;
        }

        return $data + [
            'client_id' => $this->client_id,
            'manager_id' => $this->manager_id,
            'show_rates_to_client' => (bool) $this->show_rates_to_client,
            'budget' => $this->budget,
            'budget_lines' => $this->budget_lines,
            'extras' => $this->extras,
            'team' => $this->whenLoaded('teamMembers', fn () => TeamMemberResource::collection($this->teamMembers)),
            'time_entries' => $this->whenLoaded('timeEntries', fn () => TimeEntryResource::collection($this->timeEntries)),
        ];
    }
}
