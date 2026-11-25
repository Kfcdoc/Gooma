<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tecnología asociada a un proyecto (RN-05). */
class ProjectTechnologyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'technology' => $this->whenLoaded('technology', fn () => [
                'id' => $this->technology->id,
                'name' => $this->technology->name,
                'slug' => $this->technology->slug,
            ]),
            'version' => $this->version,
            'notes' => $this->notes,
        ];
    }
}
