<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleRate;
use Tests\TestCase;

class RoleRateHistoryTest extends TestCase
{
    public function test_una_tarifa_futura_no_altera_la_vigente_en_la_fecha_del_presupuesto(): void
    {
        $this->seed();

        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();

        // Al dar de alta una tarifa nueva se cierra la anterior; el monto
        // histórico no se cambia, solo su fecha de fin de vigencia.
        RoleRate::where('role_id', $role->getKey())
            ->vigente('2026-11-25')
            ->firstOrFail()
            ->update(['effective_to' => '2027-01-01']);

        RoleRate::create([
            'role_id' => $role->getKey(),
            'currency' => RoleRate::CURRENCY_MXN,
            'rate_cents' => 50000,
            'effective_from' => '2027-01-01',
            'effective_to' => null,
            'notes' => 'Tarifa futura de prueba (RN-04).',
        ]);

        $this->assertSame(
            40000,
            RoleRate::where('role_id', $role->getKey())
                ->vigente('2026-11-25')
                ->firstOrFail()
                ->rate_cents,
        );
        $this->assertSame(
            50000,
            RoleRate::where('role_id', $role->getKey())
                ->vigente('2027-01-01')
                ->firstOrFail()
                ->rate_cents,
        );
        $this->assertSame(2, RoleRate::where('role_id', $role->getKey())->count());
    }
}
