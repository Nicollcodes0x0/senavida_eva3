<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Lista todas las organizaciones activas.
     */
    #[OA\Get(
        path: '/organizations',
        summary: 'Listar organizaciones',
        description: 'Devuelve todas las organizaciones de salud registradas en el sistema.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', description: 'Filtrar por estado: active (por defecto), inactive, all', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'all'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de organizaciones'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        $query = Organization::query();

        $status = $request->query('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $organizations = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $organizations->map(fn ($org) => [
                'id'       => $org->id,
                'name'     => $org->name,
                'isActive' => $org->is_active,
            ]),
        ], 200);
    }

    /**
     * Muestra el detalle de una organización.
     */
    #[OA\Get(
        path: '/organizations/{id}',
        summary: 'Ver una organizacion',
        description: 'Devuelve el detalle de una organizacion especifica.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la organizacion', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos de la organizacion'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso'),
            new OA\Response(response: 404, description: 'Organizacion no encontrada'),
        ]
    )]
    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $organization->id,
                'name'     => $organization->name,
                'isActive' => $organization->is_active,
            ],
        ], 200);
    }

    /**
     * Crea una organización nueva.
     * Solo un super_admin puede hacerlo.
     */
    #[OA\Post(
        path: '/organizations',
        summary: 'Crear organizacion',
        description: 'Crea una nueva organizacion de salud. Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Servicio de Salud Metropolitano'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Organizacion creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede crear organizaciones'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $organization = Organization::create([
            'name'      => $data['name'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'   => $organization->id,
                'name' => $organization->name,
            ],
        ], 201);
    }

    /**
     * Actualiza una organización existente.
     */
    #[OA\Put(
        path: '/organizations/{id}',
        summary: 'Editar una organizacion',
        description: 'Actualiza el nombre de una organizacion. Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la organizacion', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Servicio de Salud Metropolitano Actualizado'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Organizacion actualizada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede editar organizaciones'),
            new OA\Response(response: 404, description: 'Organizacion no encontrada'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function update(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $organization->fill($data);
        $organization->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $organization->id,
                'name'     => $organization->name,
                'isActive' => $organization->is_active,
            ],
        ], 200);
    }

    /**
     * Desactiva una organización (soft delete). Bloqueado si tiene
     * centros de salud activos, para no dejar el sistema en un
     * estado inconsistente.
     */
    #[OA\Delete(
        path: '/organizations/{id}',
        summary: 'Desactivar una organizacion',
        description: 'Marca la organizacion como inactiva (isActive=false). Se rechaza si tiene centros de salud activos.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la organizacion', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Organizacion desactivada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede desactivar organizaciones'),
            new OA\Response(response: 404, description: 'Organizacion no encontrada'),
            new OA\Response(response: 409, description: 'Tiene centros de salud activos, no se puede desactivar'),
        ]
    )]
    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);

        $centrosActivos = $organization->healthCenters()->where('is_active', true)->count();

        if ($centrosActivos > 0) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => "No puedes desactivar esta organizacion porque tiene {$centrosActivos} centro(s) de salud activo(s). Desactivalos primero."],
            ], 409);
        }

        $organization->is_active = false;
        $organization->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $organization->id,
                'isActive' => $organization->is_active,
            ],
        ], 200);
    }

    /**
     * Reactiva una organización previamente desactivada.
     */
    #[OA\Patch(
        path: '/organizations/{id}/restore',
        summary: 'Reactivar una organizacion',
        description: 'Marca la organizacion como activa (isActive=true). Solo el super_admin puede hacerlo.',
        tags: ['Catalogos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', description: 'UUID de la organizacion', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Organizacion reactivada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Solo super_admin puede reactivar organizaciones'),
            new OA\Response(response: 404, description: 'Organizacion no encontrada'),
        ]
    )]
    public function restore(Organization $organization): JsonResponse
    {
        $this->authorize('restore', $organization);

        $organization->is_active = true;
        $organization->save();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $organization->id,
                'isActive' => $organization->is_active,
            ],
        ], 200);
    }
}