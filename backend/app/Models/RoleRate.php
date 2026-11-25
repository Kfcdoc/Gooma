<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Tarifa por hora de un rol, vigente a partir de una fecha (RN-04).
 *
 * `rate_cents` guarda centavos de la divisa `currency` para evitar error de
 * coma flotante. Las fechas son cadenas ISO `YYYY-MM-DD`: son fechas de
 * vigencia, no instantes, por lo que no se convierten a UTC.
 */
#[Fillable(['role_id', 'currency', 'rate_cents', 'effective_from', 'effective_to', 'notes'])]
class RoleRate extends Model
{
    public const CURRENCY_MXN = 'MXN';

    protected $collection = 'role_rates';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rate_cents' => 'integer',
        ];
    }

    /**
     * Tarifa aplicable en una fecha. `effective_to` es exclusivo: una tarifa
     * termina el día anterior a la siguiente.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVigente(Builder $query, string $date): void
    {
        $query->where('effective_from', '<=', $date)
            ->where(static function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $date);
            });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
