<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/** Catálogo de tecnologías que pueden asociarse a proyectos (RN-05). */
#[Fillable(['name', 'slug', 'category', 'active'])]
class Technology extends Model
{
    protected $collection = 'technologies';

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

    public function projectTechnologies(): HasMany
    {
        return $this->hasMany(ProjectTechnology::class);
    }
}
