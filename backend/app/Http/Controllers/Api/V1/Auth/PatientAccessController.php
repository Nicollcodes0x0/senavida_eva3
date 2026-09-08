<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PatientRedeemRequest;
use App\Http\Resources\MedicalSessionResource;
use App\Models\MedicalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PatientAccessController extends Controller
{
    #[OA\Post(
        path: '/auth/patient/redeem',
        summary: 'Canjear un codigo de atencion (CTA) por un token de paciente',
        description: 'Endpoint publico. El token resultante queda acotado unicamente a la atencion que abrio ese codigo.',
        tags: ['Autenticacion'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ctaCode'],
                properties: [
                    new OA\Property(property: 'ctaCode', type: 'string', example: 'SV-535442'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token de paciente emitido correctamente'),
            new OA\Response(response: 422, description: 'Codigo invalido, expirado o de una atencion cerrada'),
        ]
    )]
    public function redeem(PatientRedeemRequest $request): JsonResponse
    {
        $ctaCode = $request->string('ctaCode')->trim()->upper()->value();

        $session = MedicalSession::query()
            ->with(['patient', 'healthCenter', 'unit', 'creator', 'closer'])
            ->where('cta_code', $ctaCode)
            ->first();

        if (! $session || ! $session->status->isOpen()) {
            throw new ApiException(
                errorCode: 'INVALID_CODE',
                message: 'El código ingresado no existe, expiró o está bloqueado.',
                statusCode: 422,
            );
        }

        $patient = $session->patient;

        return DB::transaction(function () use ($patient, $session) {
            $patient->tokens()->delete();

            $token = $patient->createToken(
                name: 'patient-portal',
                abilities: ["session:{$session->id}"],
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'token'     => $token->plainTextToken,
                    'tokenType' => 'Bearer',
                    'session'   => new MedicalSessionResource($session),
                ],
            ]);
        });
    }

    #[OA\Post(
        path: '/auth/patient/logout',
        summary: 'Cerrar sesion del portal del paciente',
        description: 'Revoca el token actual del paciente.',
        tags: ['Autenticacion'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Sesion cerrada correctamente')]
    )]
    public function logout(): JsonResponse
    {
        $request = request();
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'success']);
    }
}