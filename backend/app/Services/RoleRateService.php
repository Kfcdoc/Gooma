<?php

namespace App\Services;

use App\Models\Role;
use App\Models\RoleRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alta de tarifas históricas (RN-04).
 *
 * Una tarifa nueva cierra la vigente el día anterior; la tarifa anterior no
 * se reescribe en su monto, solo se acota su periodo de validez.
 */
class RoleRateService
{
    /** @param array<string, mixed> $data */
    public function create(Role $role, array $data): RoleRate
    {
        return DB::transaction(function () use ($role, $data): RoleRate {
            $currency = (string) ($data['currency'] ?? RoleRate::CURRENCY_MXN);
            $effectiveFrom = (string) $data['effective_from'];

            $latest = RoleRate::where('role_id', $role->getKey())
                ->where('currency', $currency)
                ->orderByDesc('effective_from')
                ->first();

            if ($latest !== null) {
                if (strcmp($effectiveFrom, (string) $latest->effective_from) <= 0) {
                    throw ValidationException::withMessages([
                        'effective_from' => ['La fecha debe ser posterior a la última tarifa registrada.'],
                    ]);
                }

                if ($latest->effective_to !== null
                    && strcmp($effectiveFrom, (string) $latest->effective_to) < 0) {
                    throw ValidationException::withMessages([
                        'effective_from' => ['La fecha se traslapa con una tarifa vigente.'],
                    ]);
                }

                if ($latest->effective_to === null) {
                    // effective_to es exclusivo: la tarifa anterior aplica hasta
                    // el día anterior a esta fecha, sin dejar hueco ni traslape.
                    $latest->update(['effective_to' => $effectiveFrom]);
                }
            }

            return RoleRate::create([
                'role_id' => $role->getKey(),
                'currency' => $currency,
                'rate_cents' => (int) $data['rate_cents'],
                'effective_from' => $effectiveFrom,
                'effective_to' => $data['effective_to'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
