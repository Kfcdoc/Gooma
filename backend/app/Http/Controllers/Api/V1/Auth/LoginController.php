<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * CU-01 Iniciar sesión.
 *
 * La API es stateless: entrega un token Bearer de Sanctum (guardado como
 * hash en MongoDB). El frontend lo envía en la cabecera Authorization.
 */
class LoginController
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['La cuenta está desactivada. Contacta al administrador.'],
            ]);
        }

        $token = $user->createToken('goomaps-api', ['*'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->loadMissing('client')),
        ]);
    }
}
