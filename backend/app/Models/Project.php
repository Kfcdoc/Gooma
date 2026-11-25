<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Proyecto de software (RN-01/RN-02).
 *
 * `manager_id` es el gestor responsable (RN-01). El presupuesto se guarda como
 * snapshot: `budget_lines` congela horas × tarifa de cada rol (RN-04) y
 * `budget` congela el desglose de extras e IVA (RN-15). Así, un cambio posterior
 * en el catálogo de tarifas no reescribe el presupuesto aprobado.
 *
 * Los aumentos de tarifa vigentes para el Mapa se consultarán en la fase 4;
 * cada movimiento financiero guardará además su propio tipo de cambio.
 */
#[Fillable([
    'code',
    'name',
    'client_id',
    'manager_id',
    'description',
    'base_currency',
    'start_date',
    'end_date',
    'status',
    'budget_initial_cents',
    'budget_initial_currency',
    'budget_lines',
    'extras',
    'budget',
])]
class Project extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $collection = 'projects';

    /** @var array<string, mixed> */
    protected $attributes = [
        'base_currency' => 'MXN',
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'budget_initial_cents' => 'integer',
            'budget_lines' => 'array',
            'extras' => 'array',
            'budget' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Gestor responsable del proyecto (RN-01). */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** RN-20: administrador ve todo; gestor ve los suyos; cliente ve el suyo. */
    public function isVisibleBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGestor()) {
            return $this->manager_id === $user->getKey();
        }

        if ($user->isCliente()) {
            return $this->client_id !== null && $this->client_id === $user->client_id;
        }

        return false;
    }

    public function isEditableBy(User $user): bool
    {
        return $user->isAdmin() || ($user->isGestor() && $this->isVisibleBy($user));
    }
}
