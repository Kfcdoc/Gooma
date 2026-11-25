<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * CU-02 Gestionar usuarios y roles (solo administrador).
 *
 * RN-19: los roles válidos son admin, gestor y cliente.
 * RN-20: solo el administrador administra las cuentas.
 */
class UserController
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::with('client')->orderBy('name')->get(),
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['role'] !== User::ROLE_CLIENTE) {
            $data['client_id'] = null;
        }

        $user = User::create($data);

        return (new UserResource($user->load('client')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('client'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        $role = $data['role'] ?? $user->role;

        if ($role !== User::ROLE_CLIENTE) {
            $data['client_id'] = null;
        }

        // Evita que el administrador se desactive a sí mismo por accidente.
        if ($request->user()->is($user) && array_key_exists('active', $data) && ! $data['active']) {
            throw ValidationException::withMessages([
                'active' => ['No puedes desactivar tu propia cuenta.'],
            ]);
        }

        $user->update($data);

        return new UserResource($user->load('client'));
    }

    public function destroy(Request $request, User $user): Response
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => ['No puedes eliminar tu propia cuenta.'],
            ]);
        }

        // Los tokens del usuario eliminado dejan de ser válidos.
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
