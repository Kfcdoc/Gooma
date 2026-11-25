<?php

namespace App\Models;

use App\Domain\Costs\HourCost;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Proyecto de software (RN-01/RN-02).
 *
 * `manager_id` es el gestor responsable (RN-01). El presupuesto se guarda como
 * snapshot: `budget_lines` congela horas × tarifa de cada rol (RN-04) y
 * `budget` congela el desglose de extras e IVA (RN-15). Así, un cambio posterior
 * en el catálogo de tarifas no reescribe el presupuesto aprobado.
 *
 * En la fase 3 se agregan equipo, tecnologías y horas. `actual_cost_cents` es
 * la suma de horas registradas × tarifa congelada (RN-03) y se recalcula al
 * crear, editar o borrar una hora.
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
    'show_rates_to_client',
    'budget_initial_cents',
    'budget_initial_currency',
    'budget_lines',
    'extras',
    'budget',
    'actual_cost_cents',
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
        'show_rates_to_client' => false,
        'actual_cost_cents' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'show_rates_to_client' => 'boolean',
            'budget_initial_cents' => 'integer',
            'budget_lines' => 'array',
            'extras' => 'array',
            'budget' => 'array',
            'actual_cost_cents' => 'integer',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeVisibleBy(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isGestor()) {
            $query->where('manager_id', $user->getKey());

            return;
        }

        if ($user->isCliente()) {
            $query->where('client_id', $user->client_id);

            return;
        }

        // Rol desconocido: sin resultados.
        $query->where('status', '__sin_acceso__');
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

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function projectTechnologies(): HasMany
    {
        return $this->hasMany(ProjectTechnology::class);
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

    /** Recalcula el costo real del proyecto tras un cambio de horas (RN-03). */
    public function refreshActualCost(): int
    {
        $cents = array_sum(array_map(
            'intval',
            $this->timeEntries()->pluck('amount_cents')->all(),
        ));

        $this->actual_cost_cents = $cents;
        $this->save();

        return $cents;
    }

    /**
     * @return array{minutes:int,hours:string,entries:int,base_cost_cents:int}
     */
    public function actualTotals(): array
    {
        $minutes = array_sum(array_map(
            'intval',
            $this->timeEntries()->pluck('minutes')->all(),
        ));

        return [
            'minutes' => $minutes,
            'hours' => HourCost::formatHours($minutes),
            'entries' => $this->timeEntries()->count(),
            'base_cost_cents' => (int) $this->actual_cost_cents,
        ];
    }
}
