<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleRate;
use App\Models\Technology;
use Illuminate\Database\Seeder;

/**
 * Catálogo de roles y tarifas de la sección 1.7 / 6.3.2 de docs/Gooma.md.
 *
 * Idempotente: repetir el seeder no duplica roles ni tarifas.
 */
class CatalogSeeder extends Seeder
{
    /** Fecha de las tarifas propuestas en la especificación. */
    public const EFFECTIVE_FROM = '2026-11-25';

    /**
     * Roles planeados, horas y tarifas propuestas en MXN por hora.
     * Las tarifas se guardan en centavos para cálculos exactos.
     *
     * @return list<array{code:string,name:string,description:string,hours:int,rate_cents:int}>
     */
    public static function plannedRoles(): array
    {
        return [
            [
                'code' => Role::CODE_GESTOR,
                'name' => 'Gestor de proyecto',
                'description' => 'Perfil intermedio; tarifa propuesta sin referencia salarial específica.',
                'hours' => 80,
                'rate_cents' => 40000,
            ],
            [
                'code' => Role::CODE_BACKEND,
                'name' => 'Desarrollador backend',
                'description' => 'Perfil intermedio; referencia general de desarrollo [1][2].',
                'hours' => 200,
                'rate_cents' => 40000,
            ],
            [
                'code' => Role::CODE_FRONTEND,
                'name' => 'Desarrollador frontend',
                'description' => 'Perfil intermedio; referencia general de desarrollo [1][2].',
                'hours' => 160,
                'rate_cents' => 37500,
            ],
            [
                'code' => Role::CODE_DEVOPS,
                'name' => 'Base de datos / DevOps',
                'description' => 'Perfil intermedio; tarifa propuesta sin referencia salarial específica.',
                'hours' => 60,
                'rate_cents' => 45000,
            ],
            [
                'code' => Role::CODE_QA,
                'name' => 'QA / pruebas',
                'description' => 'Perfil intermedio; tarifa propuesta sin referencia salarial específica.',
                'hours' => 60,
                'rate_cents' => 30000,
            ],
            [
                'code' => Role::CODE_DOCUMENTACION,
                'name' => 'Documentación',
                'description' => 'Perfil junior/intermedio; tarifa propuesta sin referencia salarial específica.',
                'hours' => 40,
                'rate_cents' => 25000,
            ],
        ];
    }

    /** @return list<array{name:string,slug:string,category:string}> */
    public static function technologies(): array
    {
        return [
            ['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend'],
            ['name' => 'React', 'slug' => 'react', 'category' => 'frontend'],
            ['name' => 'TypeScript', 'slug' => 'typescript', 'category' => 'frontend'],
            ['name' => 'MongoDB', 'slug' => 'mongodb', 'category' => 'base_de_datos'],
            ['name' => 'MariaDB', 'slug' => 'mariadb', 'category' => 'base_de_datos'],
            ['name' => 'Docker', 'slug' => 'docker', 'category' => 'devops'],
        ];
    }

    public function run(): void
    {
        foreach (self::plannedRoles() as $data) {
            $role = Role::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'active' => true,
                ],
            );

            RoleRate::updateOrCreate(
                [
                    'role_id' => $role->getKey(),
                    'currency' => RoleRate::CURRENCY_MXN,
                    'effective_from' => self::EFFECTIVE_FROM,
                ],
                [
                    'rate_cents' => $data['rate_cents'],
                    'effective_to' => null,
                    'notes' => 'Supuesto de planeación de la sección 1.7; no es un promedio de mercado.',
                ],
            );
        }

        foreach (self::technologies() as $data) {
            Technology::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'active' => true,
                ],
            );
        }
    }
}
