<?php

namespace Database\Seeders;

use App\Domain\Costs\BudgetCalculator;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Role;
use App\Models\RoleRate;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración de GooMaps.
 *
 * El seeder es idempotente: se puede correr varias veces sin duplicar
 * usuarios, roles ni proyectos, y sin reiniciar contraseñas ya cambiadas.
 * El proyecto demo cuadra exacto con el presupuesto de la sección 1.7.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::updateOrCreate(
            ['tax_id' => 'CVD010101AB1'],
            [
                'name' => 'Constructora del Valle',
                'contact_name' => 'Laura Méndez',
                'email' => 'contacto@constructora-delvalle.mx',
                'phone' => '5555123456',
                'notes' => 'Cliente de demostración para el proyecto GooMaps.',
                'active' => true,
            ],
        );

        $demoUsers = [
            [
                'email' => 'admin@goomaps.local',
                'name' => 'Administrador GooMaps',
                'role' => User::ROLE_ADMIN,
                'client_id' => null,
            ],
            [
                'email' => 'gestor@goomaps.local',
                'name' => 'Gestor de Proyectos',
                'role' => User::ROLE_GESTOR,
                'client_id' => null,
            ],
            [
                'email' => 'cliente@goomaps.local',
                'name' => 'Cliente de Constructora del Valle',
                'role' => User::ROLE_CLIENTE,
                'client_id' => $client->getKey(),
            ],
        ];

        /** @var array<string, User> $users */
        $users = [];
        foreach ($demoUsers as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->fill($data + ['active' => true]);

            // Solo se define la contraseña al crear la cuenta.
            if (! $user->exists) {
                $user->password = 'password';
            }

            $user->save();
            $users[$data['email']] = $user;
        }

        $this->seedProject($client, $users['gestor@goomaps.local']);
    }

    private function seedProject(Client $client, User $manager): void
    {
        $budgetLines = [];
        $baseCostCents = 0;

        foreach (CatalogSeeder::plannedRoles() as $planned) {
            $role = Role::where('code', $planned['code'])->firstOrFail();

            /** @var RoleRate $rate */
            $rate = RoleRate::where('role_id', $role->getKey())
                ->vigente(CatalogSeeder::EFFECTIVE_FROM)
                ->firstOrFail();

            $amountCents = $planned['hours'] * (int) $rate->rate_cents;
            $baseCostCents += $amountCents;

            $budgetLines[] = [
                'role_id' => $role->getKey(),
                'role_code' => $role->code,
                'role_name' => $role->name,
                'hours' => $planned['hours'],
                'rate_cents' => (int) $rate->rate_cents,
                'rate_currency' => $rate->currency,
                'rate_effective_from' => $rate->effective_from,
                'amount_cents' => $amountCents,
            ];
        }

        $budget = BudgetCalculator::calculate($baseCostCents, self::plannedExtras());
        $budget['currency'] = RoleRate::CURRENCY_MXN;
        $budget['calculated_at'] = now()->toIso8601String();

        $project = Project::updateOrCreate(
            ['code' => 'GOOMAPS-2026'],
            [
                'name' => 'GooMaps — Mapa del Dinero',
                'client_id' => $client->getKey(),
                'manager_id' => $manager->getKey(),
                'description' => 'Proyecto demo: presupuesto de planeación de la sección 1.7.',
                'base_currency' => RoleRate::CURRENCY_MXN,
                'start_date' => '2026-11-25',
                'end_date' => '2027-01-20',
                'status' => Project::STATUS_ACTIVE,
                'show_rates_to_client' => false,
                'actual_cost_cents' => 0,
                'budget_initial_cents' => $budget['total_cents'],
                'budget_initial_currency' => RoleRate::CURRENCY_MXN,
                'budget_lines' => $budgetLines,
                'extras' => $budget['extras'],
                'budget' => $budget,
            ],
        );

        foreach (CatalogSeeder::technologies() as $technology) {
            $model = Technology::where('slug', $technology['slug'])->firstOrFail();

            ProjectTechnology::updateOrCreate(
                [
                    'project_id' => $project->getKey(),
                    'technology_id' => $model->getKey(),
                ],
                [
                    'version' => null,
                    'notes' => null,
                ],
            );
        }
    }

    /**
     * Extras de la sección 1.7. El riesgo va activo para que el escenario demo
     * cuadre con el total publicado (342,316.00 MXN).
     *
     * @return list<array{code:string,name:string,percent:int,type:string,applies_to:string,active:bool}>
     */
    public static function plannedExtras(): array
    {
        return [
            [
                'code' => 'imprevistos',
                'name' => 'Imprevistos',
                'percent' => 10,
                'type' => BudgetCalculator::EXTRA_NECESARIO,
                'applies_to' => BudgetCalculator::BASE_COSTO_BASE,
                'active' => true,
            ],
            [
                'code' => 'riesgo_tecnologico',
                'name' => 'Riesgo tecnológico',
                'percent' => 5,
                'type' => BudgetCalculator::EXTRA_OPCIONAL,
                'applies_to' => BudgetCalculator::BASE_COSTO_BASE,
                'active' => true,
            ],
            [
                'code' => 'margen_ganancia',
                'name' => 'Margen de ganancia',
                'percent' => 15,
                'type' => BudgetCalculator::EXTRA_NECESARIO,
                'applies_to' => BudgetCalculator::BASE_COSTO_BASE,
                'active' => true,
            ],
            [
                'code' => 'iva',
                'name' => 'IVA',
                'percent' => 16,
                'type' => BudgetCalculator::EXTRA_NECESARIO,
                'applies_to' => BudgetCalculator::BASE_BASE_MAS_EXTRAS,
                'active' => true,
            ],
        ];
    }
}
