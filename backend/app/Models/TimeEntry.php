<?php

namespace App\Models;

use App\Domain\Costs\HourCost;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Hora registrada por una persona del equipo (RN-03/RN-04).
 *
 * `minutes` es entero y `rate_cents` / `amount_cents` son el snapshot de la
 * tarifa vigente en `work_date`. Un cambio del catálogo nunca reescribe esta
 * información; por eso los reportes futuros son auditables.
 */
#[Fillable(['project_id', 'team_member_id', 'role_id', 'work_date', 'minutes', 'rate_cents', 'amount_cents', 'description'])]
class TimeEntry extends Model
{
    protected $collection = 'time_entries';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'rate_cents' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (self $entry): void {
            $entry->project?->refreshActualCost();
        });

        static::deleted(static function (self $entry): void {
            $entry->project?->refreshActualCost();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hours(): string
    {
        return HourCost::formatHours((int) $this->minutes);
    }
}
