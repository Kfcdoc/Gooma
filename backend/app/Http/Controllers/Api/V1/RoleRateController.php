<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Catalog\StoreRoleRateRequest;
use App\Http\Resources\RoleRateResource;
use App\Models\Role;
use App\Models\RoleRate;
use App\Services\RoleRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/** Tarifas por rol con fecha y consulta de la vigente (CU-03, RN-04). */
class RoleRateController
{
    public function __construct(private readonly RoleRateService $service)
    {
        //
    }

    public function index(Role $role): AnonymousResourceCollection
    {
        return RoleRateResource::collection(
            $role->rates()->orderByDesc('effective_from')->get(),
        );
    }

    public function store(StoreRoleRateRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();
        $data['rate_cents'] = (int) round(((float) $data['rate']) * 100);
        unset($data['rate']);

        $rate = $this->service->create($role, $data);

        return (new RoleRateResource($rate))->response()->setStatusCode(201);
    }

    public function current(Request $request): RoleRateResource
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'code')],
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $role = Role::where('code', $validated['role'])->firstOrFail();
        $date = $validated['date'] ?? now()->toDateString();

        $rate = RoleRate::where('role_id', $role->getKey())
            ->vigente($date)
            ->firstOrFail();

        return new RoleRateResource($rate);
    }
}
