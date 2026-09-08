<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSecuritySettingRequest;
use App\Models\SecuritySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SecuritySettingController extends Controller
{
    /**
     * Devuelve la configuracion de seguridad del centro del admin.
     * Si el centro nunca la ha configurado, se crea automaticamente
     * con los valores por defecto - nunca responde 404.
     */
    #[OA\Get(
        path: '/security-settings',
        summary: 'Ver la configuracion de seguridad del propio centro',
        tags: ['Configuracion de Seguridad'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Configuracion de seguridad'),
            new OA\Response(response: 403, description: 'Sin permiso'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $admin = $request->user();

        // Verificamos el rol ANTES de tocar la base de datos. super_admin
        // no tiene centro asignado (health_center_id es null), asi que si
        // intentaramos crear el registro primero, la base de datos lo
        // rechazaria con un error feo en vez de un 403 limpio.
        if ($admin->role !== 'admin_institucional') {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FORBIDDEN_ROLE', 'message' => 'Solo un administrador institucional puede ver la configuracion de seguridad.'],
            ], 403);
        }

        $setting = SecuritySetting::firstOrCreate(
            ['health_center_id' => $admin->health_center_id],
            ['cta_max_attempts' => 3]
        );

        return response()->json([
            'success' => true,
            'data'    => $this->toArray($setting),
        ], 200);
    }

    #[OA\Put(
        path: '/security-settings',
        summary: 'Actualizar la configuracion de seguridad del propio centro',
        tags: ['Configuracion de Seguridad'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ctaMaxAttempts'],
                properties: [
                    new OA\Property(property: 'ctaMaxAttempts', type: 'integer', example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Configuracion actualizada'),
            new OA\Response(response: 403, description: 'Sin permiso'),
            new OA\Response(response: 422, description: 'Valor fuera de rango'),
        ]
    )]
    public function update(UpdateSecuritySettingRequest $request): JsonResponse
    {
        $admin = $request->user();

        $setting = SecuritySetting::firstOrCreate(
            ['health_center_id' => $admin->health_center_id],
            ['cta_max_attempts' => 3]
        );

        $setting->update($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data'    => $this->toArray($setting),
        ], 200);
    }

    private function toArray(SecuritySetting $setting): array
    {
        return [
            'id'              => $setting->id,
            'healthCenterId'  => $setting->health_center_id,
            'ctaMaxAttempts'  => $setting->cta_max_attempts,
        ];
    }
}