<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Cliente contratante de proyectos (RN-01).
 *
 * RN-20: el administrador ve todos; el gestor solo los clientes con proyectos
 * que administra; el usuario cliente solo ve su propio registro.
 */
#[Fillable(['name', 'tax_id', 'contact_name', 'email', 'phone', 'notes', 'active'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $collection = 'clients';

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

    /** @param Builder<self> $query */
    public function scopeVisibleBy(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        // CU-04: el gestor gestiona clientes. RN-20 acota los *proyectos* que
        // ve, pero no le impide crear proyectos para cualquier cliente.
        if ($user->isGestor()) {
            return;
        }

        if ($user->isCliente()) {
            $query->where('_id', $user->client_id);

            return;
        }

        $query->where('active', '__sin_acceso__');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
