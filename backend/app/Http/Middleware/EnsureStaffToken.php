<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffToken
{
    /**
     * Exige que el portador del token sea personal de salud (User),
     * nunca un Patient. Protege los endpoints clínicos y
     * administrativos que el portal del paciente no debe invocar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() instanceof Patient) {
            throw new AuthorizationException(
                'WRONG_TOKEN_TYPE|Este recurso no está disponible para el portal del paciente.'
            );
        }

        return $next($request);
    }
}