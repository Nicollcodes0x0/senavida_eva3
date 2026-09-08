<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientToken
{
    /**
     * Exige que el portador del token sea un Patient, nunca un User
     * de staff. Protege los endpoints exclusivos del paciente
     * (aprobar/rechazar/revocar consentimientos, confirmar mensajes).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Patient) {
            throw new AuthorizationException(
                'WRONG_TOKEN_TYPE|Este recurso es exclusivo del paciente.'
            );
        }

        return $next($request);
    }
}