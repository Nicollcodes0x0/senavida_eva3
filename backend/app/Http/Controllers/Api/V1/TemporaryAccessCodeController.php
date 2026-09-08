<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\SecuritySetting;
use App\Models\TemporaryAccessCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TemporaryAccessCodeController extends Controller
{
    /**
     * Genera un CTA nuevo para un paciente.
     * ENDPOINT PUBLICO: lo llama el propio paciente, sin token.
     * Cualquier CTA activo previo del mismo paciente se invalida.
     */
    #[OA\Post(
        path: '/patients/{id}/attention-codes',
        summary: 'Generar codigo de atencion (CTA)',
        description: 'Endpoint publico. El propio paciente genera su codigo temporal de atencion, indicando en que centro de salud se encuentra. El codigo se devuelve en claro UNA SOLA VEZ.',
        tags: ['Codigo de Atencion (CTA)'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del paciente', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['health_center_id'],
                properties: [
                    new OA\Property(property: 'health_center_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Codigo generado, devuelto en claro esta unica vez'),
            new OA\Response(response: 404, description: 'Paciente no encontrado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
            new OA\Response(response: 429, description: 'Demasiados intentos'),
        ]
    )]
    public function store(Request $request, Patient $patient): JsonResponse
    {
        // 1. Rate limiting: maximo 5 generaciones por IP cada 10 minutos
        $throttleKey = 'cta-generate:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw new ApiException(
                'TOO_MANY_ATTEMPTS',
                "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
                429
            );
        }

        RateLimiter::hit($throttleKey, 600);

        // 2. Validar los datos
        $data = $request->validate([
            'health_center_id' => ['required', 'uuid', 'exists:health_centers,id'],
        ]);

        // 3. Invalidar cualquier CTA activo previo de este paciente
        //    (Decision: un paciente, un codigo activo a la vez)
        TemporaryAccessCode::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        // 4. Generar el codigo y guardar SOLO su hash
        $plainCode = TemporaryAccessCode::generateCode();

        // El limite de intentos se lee de la configuracion del centro en
        // este preciso momento. Un cambio posterior en security_settings
        // NO afecta a este codigo ya emitido - cada CTA conserva el
        // limite con el que nacio (decision de diseÃ±o ya establecida).
        $maxAttempts = SecuritySetting::firstOrCreate(
            ['health_center_id' => $data['health_center_id']],
            ['cta_max_attempts' => 3]
        )->cta_max_attempts;

        $cta = TemporaryAccessCode::create([
            'patient_id'       => $patient->id,
            'health_center_id' => $data['health_center_id'],
            'code_hash'        => Hash::make($plainCode),
            'status'           => 'active',
            'expires_at'       => now()->addHour(),
            'failed_attempts'  => 0,
            'max_attempts'     => $maxAttempts,
        ]);

        // 5. Responder con el codigo EN CLARO, esta unica vez
        return response()->json([
            'success' => true,
            'data'    => [
                'code'      => $plainCode,
                'expiresAt' => $cta->expires_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Valida un CTA. Lo usa Admision cuando el paciente le muestra su codigo.
     * Busca solo entre los codigos activos del centro de salud del funcionario.
     */
    #[OA\Post(
        path: '/attention-codes/validate',
        summary: 'Validar codigo de atencion (CTA)',
        description: 'Admision escribe el codigo que el paciente le muestra. El sistema busca entre los codigos activos del centro de salud del funcionario y revela los datos del paciente si coincide.',
        tags: ['Codigo de Atencion (CTA)'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'SV-847291'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Codigo valido, devuelve datos minimos del paciente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso, o codigo bloqueado por intentos'),
            new OA\Response(response: 410, description: 'Codigo expirado'),
            new OA\Response(response: 422, description: 'Codigo invalido'),
            new OA\Response(response: 429, description: 'Demasiados intentos'),
        ]
    )]
    public function validateCode(Request $request): JsonResponse
    {
        $this->authorize('validateCode', TemporaryAccessCode::class);

        // Rate limiting: maximo 5 intentos de validacion por IP cada 5 minutos
        $throttleKey = 'cta-validate:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw new ApiException(
                'TOO_MANY_ATTEMPTS',
                "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
                429
            );
        }

        RateLimiter::hit($throttleKey, 300);

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $funcionario = $request->user();

        // 1. Buscar solo entre los CTA activos del centro del funcionario
        $query = TemporaryAccessCode::where('status', 'active');

        if ($funcionario->role !== 'super_admin') {
            $query->where('health_center_id', $funcionario->health_center_id);
        }

        $candidates = $query->get();

        // 2. Comparar uno por uno con Hash::check() (via matchesCode())
        $matchingCode = null;

        foreach ($candidates as $candidate) {
            if ($candidate->matchesCode($data['code'])) {
                $matchingCode = $candidate;
                break;
            }
        }

        // 3. Si no hay coincidencia, respuesta generica (no revela nada)
        if (! $matchingCode) {
            throw new ApiException('INVALID_CODE', 'El codigo ingresado no es valido.', 422);
        }

        // 4. Verificar expiracion
        if ($matchingCode->isExpired()) {
            $matchingCode->update(['status' => 'expired']);

            throw new ApiException('EXPIRED_CODE', 'El codigo ha expirado.', 410);
        }

        // 5. Verificar bloqueo por intentos
        if ($matchingCode->isBlocked()) {
            $matchingCode->update(['status' => 'blocked']);

            throw new ApiException('BLOCKED_CODE', 'El codigo ha sido bloqueado por demasiados intentos.', 403);
        }

        // 6. Exito: devolver datos minimos del paciente
        $patient = $matchingCode->patient;

        return response()->json([
            'success' => true,
            'data'    => [
                'accessId' => $matchingCode->id,
                'patient'  => [
                    'id'                      => $patient->id,
                    'name'                    => $patient->name,
                    'communicationPreference' => $patient->communication_preference,
                ],
            ],
        ], 200);
    }
}