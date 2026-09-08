<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\MedicalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMedicalSessionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $medicalSession = $request->route('medicalSession');

        if ($medicalSession instanceof MedicalSession && ! $medicalSession->status->isOpen()) {
            throw new ApiException(
                'SESSION_ALREADY_CLOSED',
                'Esta atencion ya esta cerrada. No se permiten mas cambios.',
                409
            );
        }

        return $next($request);
    }
}