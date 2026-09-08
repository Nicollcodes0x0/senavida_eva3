<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MedicalSessionStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdvanceMedicalSessionStageRequest;
use App\Http\Requests\CloseMedicalSessionRequest;
use App\Http\Requests\StoreMedicalSessionRequest;
use App\Http\Resources\MedicalSessionResource;
use App\Models\MedicalSession;
use App\Models\TemporaryAccessCode;
use App\Services\SystemMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class MedicalSessionController extends Controller
{
    #[OA\Post(
        path: '/medical-sessions',
        summary: 'Abrir una atencion medica',
        tags: ['Sesiones Medicas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['access_code_id', 'code', 'reason_of_visit'],
                properties: [
                    new OA\Property(property: 'access_code_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'code', type: 'string', example: 'SV-329464'),
                    new OA\Property(property: 'reason_of_visit', type: 'string'),
                    new OA\Property(property: 'allergies', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Atencion abierta correctamente')]
    )]
    public function store(StoreMedicalSessionRequest $request)
    {
        $user = $request->user();

        $accessCode = TemporaryAccessCode::findOrFail($request->validated('access_code_id'));

        if ($accessCode->health_center_id !== $user->health_center_id) {
            throw new ApiException('FORBIDDEN_CENTER', 'Este codigo pertenece a otro centro de salud.', 403);
        }

        if ($accessCode->status !== 'active') {
            throw new ApiException('CODE_ALREADY_CONSUMED', 'Este codigo ya fue utilizado o ya no esta vigente.', 409);
        }

        if ($accessCode->expires_at->isPast()) {
            throw new ApiException('EXPIRED_CODE', 'Este codigo de atencion ha expirado.', 410);
        }

        $codigoEnviado = strtoupper($request->validated('code'));

        if (! $accessCode->matchesCode($codigoEnviado)) {
            throw new ApiException('INVALID_CODE', 'El codigo no coincide con el access_code_id indicado.', 422);
        }

        $tieneAtencionAbierta = MedicalSession::where('patient_id', $accessCode->patient_id)
            ->whereIn('status', ['in_admission', 'in_triage', 'in_medical_care'])
            ->exists();

        if ($tieneAtencionAbierta) {
            throw new ApiException('PATIENT_HAS_ACTIVE_SESSION', 'Este paciente ya tiene una atencion abierta.', 409);
        }

        $session = DB::transaction(function () use ($accessCode, $request, $user, $codigoEnviado) {
            $session = MedicalSession::create([
                'patient_id' => $accessCode->patient_id,
                'organization_id' => $user->organization_id,
                'health_center_id' => $accessCode->health_center_id,
                'unit_id' => $user->unit_id,
                'temporary_access_code_id' => $accessCode->id,
                'cta_code' => $codigoEnviado,
                'status' => MedicalSessionStatus::InAdmission->value,
                'reason_of_visit' => $request->validated('reason_of_visit'),
                'allergies' => $request->validated('allergies'),
                'started_at' => now(),
                'created_by' => $user->id,
            ]);

            $accessCode->update(['status' => 'consumed', 'consumed_at' => now()]);

            return $session;
        });

        $session->load(['patient', 'creator', 'closer', 'healthCenter', 'unit']);

        return response()->json([
            'success' => true,
            'data' => new MedicalSessionResource($session),
        ], Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/medical-sessions/{id}',
        summary: 'Ver el detalle de una atencion medica',
        tags: ['Sesiones Medicas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Detalle de la sesion medica')]
    )]
    public function show(MedicalSession $medicalSession)
    {
        $this->authorize('view', $medicalSession);

        $medicalSession->load(['patient', 'creator', 'closer', 'healthCenter', 'unit', 'triageSkippedBy']);

        return response()->json([
            'success' => true,
            'data' => new MedicalSessionResource($medicalSession),
        ]);
    }

    #[OA\Get(
        path: '/medical-sessions/active',
        summary: 'Listar las atenciones activas de mi unidad',
        tags: ['Sesiones Medicas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Lista de atenciones activas')]
    )]
    public function active(Request $request)
    {
        $this->authorize('viewAny', MedicalSession::class);

        $user = $request->user();

        $sessions = MedicalSession::where('health_center_id', $user->health_center_id)
            ->where('unit_id', $user->unit_id)
            ->whereIn('status', ['in_admission', 'in_triage', 'in_medical_care'])
            ->with(['patient', 'creator', 'closer', 'healthCenter', 'unit', 'triageSkippedBy'])
            ->orderBy('started_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => MedicalSessionResource::collection($sessions),
        ]);
    }

    /**
     * S4 - Avanzar de etapa. Modo normal: solo el dueno del tramo.
     * Modo emergencia (D-23): medico salta directo a consulta medica.
     */
    #[OA\Patch(
        path: '/medical-sessions/{id}/stage',
        summary: 'Avanzar la atencion a la siguiente etapa',
        description: 'Modo normal: admision/categorizacion avanzan su tramo. Modo emergencia: medico salta directo a consulta medica con "emergency": true y "reason" (minimo 30 caracteres).',
        tags: ['Sesiones Medicas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'emergency', type: 'boolean', example: true),
                    new OA\Property(property: 'reason', type: 'string', example: 'Paciente con dolor toracico y disnea, sospecha de infarto agudo.'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Sesion avanzada')]
    )]
    public function advance(AdvanceMedicalSessionStageRequest $request, MedicalSession $medicalSession)
    {
        if ($request->boolean('emergency')) {
            return $this->advanceEmergency($request, $medicalSession);
        }

        $this->authorize('advance', $medicalSession);

        $siguienteEtapa = $medicalSession->status->next();

        if ($siguienteEtapa === null) {
            throw new ApiException('INVALID_STAGE_TRANSITION', 'Esta atencion no tiene una siguiente etapa disponible.', 409);
        }

        $medicalSession->update(['status' => $siguienteEtapa->value]);

        SystemMessageService::create(
            $medicalSession,
            "La atencion avanzo a {$siguienteEtapa->label()}."
        );

        $medicalSession->load(['patient', 'creator', 'closer', 'healthCenter', 'unit']);

        return response()->json([
            'success' => true,
            'data' => new MedicalSessionResource($medicalSession),
        ]);
    }

    /**
     * D-23: salto de emergencia. Solo medico. Registra motivo y autor
     * para auditoria posterior.
     */
    private function advanceEmergency(AdvanceMedicalSessionStageRequest $request, MedicalSession $medicalSession)
    {
        $this->authorize('emergencyAdvance', $medicalSession);

        $medicalSession->update([
            'status' => MedicalSessionStatus::InMedicalCare->value,
            'triage_skipped' => true,
            'triage_skip_reason' => $request->validated('reason'),
            'triage_skipped_by' => $request->user()->id,
        ]);

        SystemMessageService::create(
            $medicalSession,
            'La atencion salto directamente a Consulta Medica por criterio de emergencia.'
        );

        $medicalSession->load(['patient', 'creator', 'closer', 'healthCenter', 'unit', 'triageSkippedBy']);

        return response()->json([
            'success' => true,
            'data' => new MedicalSessionResource($medicalSession),
        ]);
    }

    #[OA\Post(
        path: '/medical-sessions/{id}/close',
        summary: 'Cerrar una atencion medica',
        tags: ['Sesiones Medicas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['closure_reason', 'summary'],
                properties: [
                    new OA\Property(property: 'closure_reason', type: 'string', enum: ['completed', 'referred', 'abandoned']),
                    new OA\Property(property: 'summary', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Atencion cerrada correctamente')]
    )]
    public function close(CloseMedicalSessionRequest $request, MedicalSession $medicalSession)
    {
        $this->authorize('close', $medicalSession);

        if (! $medicalSession->status->isOpen()) {
            throw new ApiException('SESSION_ALREADY_CLOSED', 'Esta atencion ya esta cerrada.', 409);
        }

        $medicalSession->closeSession(
            closureReason: $request->validated('closure_reason'),
            summary: $request->validated('summary'),
            closedBy: $request->user(),
        );

        $medicalSession->load(['patient', 'creator', 'closer', 'healthCenter', 'unit']);

        return response()->json([
            'success' => true,
            'data' => new MedicalSessionResource($medicalSession),
        ]);
    }
}