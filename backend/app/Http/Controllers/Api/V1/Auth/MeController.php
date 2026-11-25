<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/** Devuelve el usuario autenticado y su cliente (si aplica). */
class MeController
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user()->loadMissing('client'));
    }
}
