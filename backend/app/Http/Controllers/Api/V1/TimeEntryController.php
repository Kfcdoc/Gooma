<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Costs\HourCost;
use App\Http\Requests\Hours\StoreTimeEntryRequest;
use App\Http\Requests\Hours\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** CU-07: registrar horas con tarifa vigente congelada (RN-03/RN-04). */
class TimeEntryController
{
    public function __construct(private readonly TimeEntryService $service)
    {
        //
    }

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);

        $query = $project->timeEntries()
            ->with(['teamMember', 'role'])
            ->orderByDesc('work_date');

        if ($request->filled('team_member_id')) {
            $query->where('team_member_id', $request->string('team_member_id')->toString());
        }

        return TimeEntryResource::collection($query->get());
    }

    public function store(StoreTimeEntryRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $member = TeamMember::whereKey($request->validated('team_member_id'))->firstOrFail();
        $data = $this->service->dataFor($project, $member, $request->validated());

        $entry = $project->timeEntries()->create($data);

        return (new TimeEntryResource($entry->load(['teamMember', 'role'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project, string $timeEntry): TimeEntryResource
    {
        Gate::authorize('view', $project);

        return new TimeEntryResource($this->entry($project, $timeEntry)->load(['teamMember', 'role']));
    }

    public function update(
        UpdateTimeEntryRequest $request,
        Project $project,
        string $timeEntry,
    ): TimeEntryResource {
        Gate::authorize('update', $project);

        $entry = $this->entry($project, $timeEntry);
        $validated = $request->validated();

        $merged = [
            'work_date' => $validated['work_date'] ?? $entry->work_date,
            'hours' => $validated['hours'] ?? HourCost::formatHours((int) $entry->minutes),
            'description' => array_key_exists('description', $validated)
                ? $validated['description']
                : $entry->description,
        ];

        /** @var TeamMember $member */
        $member = $entry->teamMember;
        $entry->update($this->service->dataFor($project, $member, $merged));

        return new TimeEntryResource($entry->load(['teamMember', 'role']));
    }

    public function destroy(Project $project, string $timeEntry): Response
    {
        Gate::authorize('update', $project);

        $this->entry($project, $timeEntry)->delete();

        return response()->noContent();
    }

    private function entry(Project $project, string $id): TimeEntry
    {
        return $project->timeEntries()->whereKey($id)->firstOrFail();
    }
}
