<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea usuarios desactivados aunque conserven un token válido.
 * El administrador puede reactivarlos desde la gestión de usuarios.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->active) {
            abort(403, 'Tu cuenta está desactivada. Contacta al administrador.');
        }

        return $next($request);
    }
}
