<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** CU-05/CU-16: gestión de proyectos y consulta de solo lectura del cliente. */
class ProjectController
{
    private const DETAIL_RELATIONS = [
        'client',
        'manager',
        'teamMembers.role',
        'timeEntries.teamMember',
        'timeEntries.role',
        'projectTechnologies.technology',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Project::class);

        $query = Project::visibleBy($request->user())
            ->with(['client', 'manager', 'projectTechnologies.technology']);

        foreach (['client_id', 'manager_id', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter)->toString());
            }
        }

        return ProjectResource::collection(
            $query->orderBy('name')->get(),
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $data = $request->validated();
        $currency = $data['base_currency'] ?? 'MXN';

        if (empty($data['code'])) {
            unset($data['code']);
        }

        $data += [
            'code' => 'PRJ-'.now()->year.'-'.strtoupper(Str::random(5)),
            'description' => null,
            'base_currency' => $currency,
            'status' => Project::STATUS_ACTIVE,
            'show_rates_to_client' => false,
            'budget_initial_cents' => 0,
            'budget_initial_currency' => $currency,
            'budget_lines' => [],
            'extras' => [],
            'budget' => [
                'base_cost_cents' => 0,
                'extras_cents' => 0,
                'subtotal_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 0,
                'currency' => $currency,
            ],
            'actual_cost_cents' => 0,
        ];

        $project = Project::create($data);

        return (new ProjectResource($project->load(self::DETAIL_RELATIONS)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return new ProjectResource($project->load(self::DETAIL_RELATIONS));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        Gate::authorize('update', $project);

        $data = $request->validated();

        // RN-20: un gestor no puede reasignar su proyecto a otro gestor.
        if ($request->user()?->isGestor()) {
            unset($data['manager_id']);
        }

        $start = $data['start_date'] ?? $project->start_date;
        $end = $data['end_date'] ?? $project->end_date;

        if ($start !== null && $end !== null && $end < $start) {
            throw ValidationException::withMessages([
                'end_date' => ['La fecha de fin no puede ser anterior al inicio.'],
            ]);
        }

        $project->update($data);

        return new ProjectResource($project->load(self::DETAIL_RELATIONS));
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);

        DB::transaction(function () use ($project): void {
            $project->timeEntries()->delete();
            $project->teamMembers()->delete();
            $project->projectTechnologies()->delete();
            $project->delete();
        });

        return response()->noContent();
    }
}
