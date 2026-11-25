<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Cierra la sesión en todos los dispositivos del usuario. */
class LogoutAllController
{
    public function __invoke(Request $request): Response
    {
        $request->user()?->tokens()->delete();

        return response()->noContent();
    }
}
