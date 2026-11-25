<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

/** Asociación proyecto ↔ tecnología (RN-05), con versión y nota opcionales. */
#[Fillable(['project_id', 'technology_id', 'version', 'notes'])]
class ProjectTechnology extends Model
{
    protected $collection = 'project_technologies';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }
}
