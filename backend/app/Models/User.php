<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Auth\User as Authenticatable;

/**
 * Usuario de GooMaps.
 *
 * RN-19: existen tres roles (administrador, gestor y cliente).
 * RN-20: el gestor solo opera sus proyectos y el cliente solo consulta.
 *
 * El modelo extiende la base de MongoDB para que Eloquent use la conexión
 * `mongodb` y herede el manejo de `_id` como string.
 */
#[Fillable(['name', 'email', 'password', 'role', 'client_id', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_GESTOR = 'gestor';

    public const ROLE_CLIENTE = 'cliente';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_GESTOR, self::ROLE_CLIENTE];

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => self::ROLE_GESTOR,
        'active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'client_id' => 'string',
        ];
    }

    /** Normaliza el correo para que el índice único sea consistente. */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => Str::lower(trim($value)),
        );
    }

    /** Cliente al que pertenece el usuario (solo rol cliente). */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isGestor(): bool
    {
        return $this->role === self::ROLE_GESTOR;
    }

    public function isCliente(): bool
    {
        return $this->role === self::ROLE_CLIENTE;
    }
}
