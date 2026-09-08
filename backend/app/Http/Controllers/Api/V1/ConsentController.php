<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConsentStatus;
use App\Enums\ConsentType;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsentRequestRequest;
use App\Http\Resources\ConsentResource;
use App\Models\Consent;
use App\Models\MedicalSession;
use App\Models\PatientContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConsentController extends Controller
{
    #[OA\Get(
        path: '/medical-sessions/{id}/consents',
        summary: 'Listar los consentimientos de una atencion',
        tags: ['Consentimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de consentimientos')]
    )]
    public function index(MedicalSession $medicalSession): JsonResponse
    {
        $this->authorize('viewAny', [Consent::class, $medicalSession]);

        $consents = Consent::where('medical_session_id', $medicalSession->id)
            ->with(['requester', 'contact'])
            ->orderBy('requested_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ConsentResource::collection($consents),
        ]);
    }

    #[OA\Post(
        path: '/medical-sessions/{id}/consent-requests',
        summary: 'Solicitar un consentimiento al paciente',
        description: 'Exclusivo del medico. El titulo y la descripcion se generan desde plantilla, no los escribe el solicitante.',
        tags: ['Consentimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['consentType'],
                properties: [
                    new OA\Property(property: 'consentType', type: 'string', enum: ['start_care', 'clinical_data', 'camera', 'share_with_contacts']),
                    new OA\Property(property: 'contactId', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Solicitud creada')]
    )]
    public function store(StoreConsentRequestRequest $request, MedicalSession $medicalSession): JsonResponse
    {
        $this->authorize('create', [Consent::class, $medicalSession]);

        $tipo = ConsentType::from($request->validated('consentType'));
        $contactId = $request->validated('contactId');

        // El contacto DEBE pertenecer al paciente de esta atencion:
        // sin esto, se podria autorizar el envio de datos clinicos
        // al contacto de otro paciente.
        if ($contactId !== null) {
            $contacto = PatientContact::find($contactId);

            if ($contacto === null || $contacto->patient_id !== $medicalSession->patient_id) {
                throw new ApiException(
                    'FORBIDDEN_CONTACT',
                    'Ese contacto no pertenece al paciente de esta atención.',
                    422,
                );
            }
        }

        $consent = Consent::create([
            'medical_session_id' => $medicalSession->id,
            'patient_id'         => $medicalSession->patient_id,
            'requested_by'       => $request->user()->id,
            'consent_type'       => $tipo->value,
            'patient_contact_id' => $contactId,
            'status'             => ConsentStatus::Pending->value,
            'requested_at'       => now(),
        ]);

        $consent->load(['requester', 'contact']);

        return response()->json([
            'success' => true,
            'data' => new ConsentResource($consent),
        ], 201);
    }

    #[OA\Post(
        path: '/consent-requests/{id}/approve',
        summary: 'Aprobar un consentimiento',
        description: 'Exclusivo del paciente dueno de la atencion.',
        tags: ['Consentimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Consentimiento otorgado')]
    )]
    public function approve(Request $request, Consent $consent): JsonResponse
    {
        $this->authorize('respond', $consent);

        $consent->approve($this->evidencia($request));
        $consent->load(['requester', 'contact']);

        return response()->json([
            'success' => true,
            'data' => new ConsentResource($consent),
        ]);
    }

    #[OA\Post(
        path: '/consent-requests/{id}/reject',
        summary: 'Rechazar un consentimiento',
        description: 'Exclusivo del paciente dueno de la atencion.',
        tags: ['Consentimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Consentimiento rechazado')]
    )]
    public function reject(Request $request, Consent $consent): JsonResponse
    {
        $this->authorize('respond', $consent);

        $consent->reject($this->evidencia($request));
        $consent->load(['requester', 'contact']);

        return response()->json([
            'success' => true,
            'data' => new ConsentResource($consent),
        ]);
    }

    #[OA\Post(
        path: '/consent-requests/{id}/revoke',
        summary: 'Revocar un consentimiento ya otorgado',
        description: 'Exclusivo del paciente. Solo aplica a consentimientos en estado otorgado.',
        tags: ['Consentimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Consentimiento revocado')]
    )]
    public function revoke(Request $request, Consent $consent): JsonResponse
    {
        $this->authorize('respond', $consent);

        $consent->revoke($this->evidencia($request));
        $consent->load(['requester', 'contact']);

        return response()->json([
            'success' => true,
            'data' => new ConsentResource($consent),
        ]);
    }

    /**
     * Evidencia de la decision del paciente, exigida por el contrato:
     * deja rastro de desde donde se autorizo.
     */
    private function evidencia(Request $request): array
    {
        return [
            'ip'        => $request->ip(),
            'userAgent' => $request->userAgent(),
            'at'        => now()->toIso8601String(),
        ];
    }
}