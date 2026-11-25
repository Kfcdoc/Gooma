<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Projects\StoreProjectTechnologyRequest;
use App\Http\Resources\ProjectTechnologyResource;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** CU-06: asociar tecnologías del catálogo a un proyecto (RN-05). */
class ProjectTechnologyController
{
    public function index(Project $project): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);

        return ProjectTechnologyResource::collection(
            $project->projectTechnologies()->with('technology')->orderBy('created_at')->get(),
        );
    }

    public function store(StoreProjectTechnologyRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validated();
        Technology::whereKey($data['technology_id'])->firstOrFail();

        $pivot = $project->projectTechnologies()->updateOrCreate(
            ['technology_id' => $data['technology_id']],
            [
                'version' => $data['version'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        );

        return (new ProjectTechnologyResource($pivot->load('technology')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Project $project, string $technology): Response
    {
        Gate::authorize('update', $project);

        $project->projectTechnologies()
            ->where('technology_id', $technology)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }
}
