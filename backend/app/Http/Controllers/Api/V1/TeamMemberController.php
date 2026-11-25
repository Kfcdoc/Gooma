<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Project;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** CU-06: asignar personas y roles a un proyecto. */
class TeamMemberController
{
    public function index(Project $project): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);

        return TeamMemberResource::collection(
            $project->teamMembers()->with('role')->orderBy('name')->get(),
        );
    }

    public function store(StoreTeamMemberRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $member = $project->teamMembers()->create($request->validated());

        return (new TeamMemberResource($member->load('role')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project, string $teamMember): TeamMemberResource
    {
        Gate::authorize('view', $project);

        return new TeamMemberResource($this->member($project, $teamMember)->load('role'));
    }

    public function update(
        UpdateTeamMemberRequest $request,
        Project $project,
        string $teamMember,
    ): TeamMemberResource {
        Gate::authorize('update', $project);

        $member = $this->member($project, $teamMember);
        $member->update($request->validated());

        return new TeamMemberResource($member->load('role'));
    }

    public function destroy(Project $project, string $teamMember): Response
    {
        Gate::authorize('update', $project);

        $member = $this->member($project, $teamMember);

        if ($member->timeEntries()->exists()) {
            throw ValidationException::withMessages([
                'team_member' => ['No se puede eliminar una persona con horas registradas.'],
            ]);
        }

        $member->delete();

        return response()->noContent();
    }

    private function member(Project $project, string $id): TeamMember
    {
        return $project->teamMembers()->whereKey($id)->firstOrFail();
    }
}
