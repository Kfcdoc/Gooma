<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Catalog\StoreRoleRequest;
use App\Http\Requests\Catalog\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/** Catálogo de roles de trabajo (CU-03, RN-04). */
class RoleController
{
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(Role::orderBy('name')->get());
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('rates'));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create($request->validated());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role->update($request->validated());

        return new RoleResource($role->load('rates'));
    }

    public function destroy(Role $role): Response
    {
        if ($role->rates()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['No se puede eliminar un rol con tarifas históricas.'],
            ]);
        }

        $role->delete();

        return response()->noContent();
    }
}
