<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Catálogo de roles de trabajo (RN-03/RN-04).
 *
 * No confundir con los roles de acceso de RN-19 (admin/gestor/cliente): este
 * catálogo describe perfiles tarifados como "Desarrollador backend".
 */
#[Fillable(['code', 'name', 'description', 'active'])]
class Role extends Model
{
    public const CODE_GESTOR = 'gestor_proyecto';

    public const CODE_BACKEND = 'desarrollador_backend';

    public const CODE_FRONTEND = 'desarrollador_frontend';

    public const CODE_DEVOPS = 'base_datos_devops';

    public const CODE_QA = 'qa_pruebas';

    public const CODE_DOCUMENTACION = 'documentacion';

    protected $collection = 'roles';

    /** @var array<string, mixed> */
    protected $attributes = [
        'active' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /** Tarifas históricas de este rol; un cambio no altera las anteriores. */
    public function rates(): HasMany
    {
        return $this->hasMany(RoleRate::class);
    }
}
