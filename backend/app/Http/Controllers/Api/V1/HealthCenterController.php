<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\HealthCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCenterController extends Controller
{
    /**
     * Lista todos los centros de salud activos.
     */
    #[OA\Get(
        path: '/health-centers',
        summary: 'Listar centros de salud',
        description: 'Devuelve todos los centros de salud, cada uno con el organizationId al que pertenece.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', description: 'Filtrar por estado: active (por defecto), inactive, all', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'all'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de centros de salud'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HealthCenter::class);

        $query = HealthCenter::query();

        $status = $request->query('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $healthCenters = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $healthCenters->map(fn ($center) => [
                'id'             => $center->id,
                'name'           => $center->name,
                'organizationId' => $center->organization_id,
                'isActive'       => $center->is_active,
            ]),
        ], 200);
    }

    /**
     * Muestra el detalle de un centro de salud.
     */
    #[OA\Get(
        path: '/health-centers/{id}',
        summary: 'Ver un centro de salud',
        description: 'Devuelve el detalle de un centro de salud especifico.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del centro de salud', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos del centro de salud'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso'),
            new OA\Response(response: 404, description: 'Centro de salud no encontrado'),
        ]
    )]
    public function show(HealthCenter $healthCenter): JsonResponse
    {
        $this->authorize('view', $healthCenter);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $healthCenter->id,
                'name'           => $healthCenter->name,
                'organizationId' => $healthCenter->organization_id,
                'isActive'       => $healthCenter->is_active,
            ],
        ], 200);
    }

    /**
     * Crea un centro de salud nuevo.
     * Solo un super_admin puede hacerlo.
     */
    #[OA\Post(
        path: '/health-centers',
        summary: 'Crear centro de salud',
        description: 'Crea un nuevo centro de salud dentro de una organizacion. Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'organizationId'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Hospital San Rafael'),
                    new OA\Property(property: 'organizationId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Centro de salud creado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede crear centros'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', HealthCenter::class);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'organizationId' => ['required', 'uuid', 'exists:organizations,id'],
        ]);

        $healthCenter = HealthCenter::create([
            'name'            => $data['name'],
            'organization_id' => $data['organizationId'],
            'is_active'       => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $healthCenter->id,
                'name'           => $healthCenter->name,
                'organizationId' => $healthCenter->organization_id,
            ],
        ], 201);
    }

    /**
     * Actualiza un centro de salud existente.
     */
    #[OA\Put(
        path: '/health-centers/{id}',
        summary: 'Editar un centro de salud',
        description: 'Actualiza los datos de un centro de salud. Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del centro de salud', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Hospital San Rafael Actualizado'),
                    new OA\Property(property: 'organizationId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Centro de salud actualizado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede editar centros'),
            new OA\Response(response: 404, description: 'Centro de salud no encontrado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function update(Request $request, HealthCenter $healthCenter): JsonResponse
    {
        $this->authorize('update', $healthCenter);

        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'organizationId' => ['sometimes', 'uuid', 'exists:organizations,id'],
        ]);

        $healthCenter->fill($data);
        $healthCenter->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $healthCenter->id,
                'name'           => $healthCenter->name,
                'organizationId' => $healthCenter->organization_id,
                'isActive'       => $healthCenter->is_active,
            ],
        ], 200);
    }

    /**
     * Desactiva un centro de salud (soft delete). Bloqueado si tiene
     * unidades activas, para no dejar el sistema en un estado
     * inconsistente.
     */
    #[OA\Delete(
        path: '/health-centers/{id}',
        summary: 'Desactivar un centro de salud',
        description: 'Marca el centro de salud como inactivo (isActive=false). Se rechaza si tiene unidades activas.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del centro de salud', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Centro de salud desactivado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede desactivar centros'),
            new OA\Response(response: 404, description: 'Centro de salud no encontrado'),
            new OA\Response(response: 409, description: 'Tiene unidades activas, no se puede desactivar'),
        ]
    )]
    public function destroy(HealthCenter $healthCenter): JsonResponse
    {
        $this->authorize('delete', $healthCenter);

        $unidadesActivas = $healthCenter->units()->where('is_active', true)->count();

        if ($unidadesActivas > 0) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => "No puedes desactivar este centro porque tiene {$unidadesActivas} unidad(es) activa(s). Desactivalas primero."],
            ], 409);
        }

        $healthCenter->is_active = false;
        $healthCenter->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $healthCenter->id,
                'isActive' => $healthCenter->is_active,
            ],
        ], 200);
    }

    /**
     * Reactiva un centro de salud previamente desactivado.
     */
    #[OA\Patch(
        path: '/health-centers/{id}/restore',
        summary: 'Reactivar un centro de salud',
        description: 'Marca el centro como activo (isActive=true). Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID del centro de salud', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Centro de salud reactivado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede reactivar centros'),
            new OA\Response(response: 404, description: 'Centro de salud no encontrado'),
        ]
    )]
    public function restore(HealthCenter $healthCenter): JsonResponse
    {
        $this->authorize('restore', $healthCenter);

        $healthCenter->is_active = true;
        $healthCenter->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $healthCenter->id,
                'isActive' => $healthCenter->is_active,
            ],
        ], 200);
    }
}