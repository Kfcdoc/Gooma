<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Cliente contratante de proyectos.
 *
 * En la fase 2 solo se crea el modelo y los datos demo necesarios para
 * vincular usuarios con rol cliente. El CRUD completo llega en la fase 3.
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
