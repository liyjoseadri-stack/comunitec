<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolUsuario
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $solicitud, Closure $siguiente, string ...$roles): Response
    {
        if (! $solicitud->user() || ! in_array($solicitud->user()->rol, $roles, true)) {
            abort(403);
        }

        return $siguiente($solicitud);
    }
}
