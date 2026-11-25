<?php

namespace App\Services;

use App\Domain\Costs\HourCost;
use App\Models\Project;
use App\Models\RoleRate;
use App\Models\TeamMember;
use Illuminate\Validation\ValidationException;

/**
 * Registro de horas con tarifa vigente congelada (RN-03/RN-04).
 *
 * La conversión entre divisas llega en la fase 4; por ahora la tarifa debe
 * estar en la divisa base del proyecto para no mezclar importes.
 */
class TimeEntryService
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function dataFor(Project $project, TeamMember $member, array $validated): array
    {
        if ($member->project_id !== $project->getKey() || ! $member->active) {
            throw ValidationException::withMessages([
                'team_member_id' => ['La persona no pertenece al proyecto o está inactiva.'],
            ]);
        }

        $workDate = (string) $validated['work_date'];

        /** @var RoleRate|null $rate */
        $rate = RoleRate::where('role_id', $member->role_id)
            ->vigente($workDate)
            ->first();

        if ($rate === null) {
            throw ValidationException::withMessages([
                'work_date' => ['No hay tarifa vigente para el rol de esta persona en esa fecha.'],
            ]);
        }

        if ($rate->currency !== $project->base_currency) {
            throw ValidationException::withMessages([
                'work_date' => ['La tarifa vigente está en otra divisa; la conversión se agrega en la fase 4.'],
            ]);
        }

        $minutes = HourCost::minutesFromHours($validated['hours']);

        return [
            'project_id' => $project->getKey(),
            'team_member_id' => $member->getKey(),
            'role_id' => $member->role_id,
            'work_date' => $workDate,
            'minutes' => $minutes,
            'rate_cents' => (int) $rate->rate_cents,
            'amount_cents' => HourCost::amountCents((int) $rate->rate_cents, $minutes),
            'description' => $validated['description'] ?? null,
        ];
    }
}
