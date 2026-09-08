<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Lista las unidades activas. Si se envía healthCenterId,
     * filtra solo las unidades de ese centro.
     */
    #[OA\Get(
        path: '/units',
        summary: 'Listar unidades',
        description: 'Devuelve las unidades del sistema. Si se envia el parametro healthCenterId, filtra solo las de ese centro.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'healthCenterId', description: 'UUID del centro de salud para filtrar sus unidades (opcional)', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'status', description: 'Filtrar por estado: active (por defecto), inactive, all', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'all'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de unidades'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $query = Unit::query();

        $status = $request->query('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->has('healthCenterId')) {
            $query->where('health_center_id', $request->query('healthCenterId'));
        }

        $units = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $units->map(fn ($unit) => [
                'id'             => $unit->id,
                'name'           => $unit->name,
                'healthCenterId' => $unit->health_center_id,
                'isActive'       => $unit->is_active,
            ]),
        ], 200);
    }

    /**
     * Muestra el detalle de una unidad.
     */
    #[OA\Get(
        path: '/units/{id}',
        summary: 'Ver una unidad',
        description: 'Devuelve el detalle de una unidad especifica. El admin_institucional solo puede ver unidades de su propio centro.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la unidad', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos de la unidad'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para ver esta unidad'),
            new OA\Response(response: 404, description: 'Unidad no encontrada'),
        ]
    )]
    public function show(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $unit->id,
                'name'           => $unit->name,
                'healthCenterId' => $unit->health_center_id,
                'isActive'       => $unit->is_active,
            ],
        ], 200);
    }

    /**
     * Crea una unidad nueva.
     * super_admin: puede crear en cualquier centro.
     * admin_institucional: solo puede crear en SU propio centro.
     */
    #[OA\Post(
        path: '/units',
        summary: 'Crear unidad',
        description: 'Crea una unidad dentro de un centro de salud. El super_admin puede crearla en cualquier centro; el admin_institucional solo en el suyo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'healthCenterId'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Urgencia Adulto'),
                    new OA\Property(property: 'healthCenterId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Unidad creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso, o fuera del propio centro'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $user = $request->user();

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'healthCenterId' => ['required', 'uuid', 'exists:health_centers,id'],
        ]);

        if ($user->role === 'admin_institucional' && $data['healthCenterId'] !== $user->health_center_id) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Solo puedes crear unidades en tu propio centro de salud.'],
            ], 403);
        }

        $unit = Unit::create([
            'name'             => $data['name'],
            'health_center_id' => $data['healthCenterId'],
            'is_active'        => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $unit->id,
                'name'           => $unit->name,
                'healthCenterId' => $unit->health_center_id,
            ],
        ], 201);
    }

    /**
     * Actualiza una unidad existente.
     */
    #[OA\Put(
        path: '/units/{id}',
        summary: 'Editar una unidad',
        description: 'Actualiza los datos de una unidad. El admin_institucional solo puede editar unidades de su propio centro.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la unidad', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Urgencia Adulto Actualizada'),
                    new OA\Property(property: 'healthCenterId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Unidad actualizada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para editar esta unidad'),
            new OA\Response(response: 404, description: 'Unidad no encontrada'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'healthCenterId' => ['sometimes', 'uuid', 'exists:health_centers,id'],
        ]);

        $unit->fill($data);
        $unit->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $unit->id,
                'name'           => $unit->name,
                'healthCenterId' => $unit->health_center_id,
                'isActive'       => $unit->is_active,
            ],
        ], 200);
    }

    /**
     * Desactiva una unidad (soft delete). Bloqueado si tiene usuarios
     * activos, para no dejar el sistema en un estado inconsistente.
     */
    #[OA\Delete(
        path: '/units/{id}',
        summary: 'Desactivar una unidad',
        description: 'Marca la unidad como inactiva (isActive=false). Se rechaza si tiene usuarios activos.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la unidad', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unidad desactivada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para desactivar esta unidad'),
            new OA\Response(response: 404, description: 'Unidad no encontrada'),
            new OA\Response(response: 409, description: 'Tiene usuarios activos, no se puede desactivar'),
        ]
    )]
    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $usuariosActivos = User::where('unit_id', $unit->id)->where('is_active', true)->count();

        if ($usuariosActivos > 0) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => "No puedes desactivar esta unidad porque tiene {$usuariosActivos} usuario(s) activo(s). Desactivalos primero."],
            ], 409);
        }

        $unit->is_active = false;
        $unit->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $unit->id,
                'isActive' => $unit->is_active,
            ],
        ], 200);
    }

    /**
     * Reactiva una unidad previamente desactivada.
     */
    #[OA\Patch(
        path: '/units/{id}/restore',
        summary: 'Reactivar una unidad',
        description: 'Marca la unidad como activa (isActive=true). El admin_institucional solo puede reactivar unidades de su propio centro.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la unidad', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Unidad reactivada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para reactivar esta unidad'),
            new OA\Response(response: 404, description: 'Unidad no encontrada'),
        ]
    )]
    public function restore(Unit $unit): JsonResponse
    {
        $this->authorize('restore', $unit);

        $unit->is_active = true;
        $unit->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $unit->id,
                'isActive' => $unit->is_active,
            ],
        ], 200);
    }
}