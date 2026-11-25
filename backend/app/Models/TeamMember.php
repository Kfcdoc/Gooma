<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Persona asignada a un proyecto con un rol de trabajo (RN-03).
 *
 * No es necesario que sea usuario del sistema: puede ser una persona externa.
 * El rol define la tarifa vigente; la tarifa se congela en cada hora registrada.
 */
#[Fillable(['project_id', 'role_id', 'name', 'email', 'active', 'notes'])]
class TeamMember extends Model
{
    protected $collection = 'team_members';

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Rol de trabajo del catálogo (RN-04), no el rol de acceso del usuario. */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
