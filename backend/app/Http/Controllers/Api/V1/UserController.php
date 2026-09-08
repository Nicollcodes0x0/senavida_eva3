<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    private const SORTABLE = [
        'name'      => 'name',
        'email'     => 'email',
        'role'      => 'role',
        'createdAt' => 'created_at',
    ];

    /**
     * Lista los usuarios visibles para quien hace la peticion.
     * super_admin ve todos; admin_institucional solo los de su propio centro.
     */
    #[OA\Get(
        path: '/users',
        summary: 'Listar usuarios',
        description: 'Devuelve los usuarios visibles para quien hace la peticion, paginados.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'healthCenterId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'unitId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'name')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado de usuarios'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso para listar usuarios'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $admin = $request->user();
        $query = User::query();

        if ($admin->role === 'admin_institucional') {
            $query->where('health_center_id', $admin->health_center_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }
        if ($request->filled('healthCenterId')) {
            $query->where('health_center_id', $request->query('healthCenterId'));
        }
        if ($request->filled('unitId')) {
            $query->where('unit_id', $request->query('unitId'));
        }
        if ($request->has('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        $this->applySort($query, $request->query('sort'));

        $perPage = min(max((int) $request->query('perPage', 25), 1), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => collect($paginated->items())->map(fn ($user) => $this->toArray($user)),
            'meta'    => [
                'pagination' => [
                    'total'       => $paginated->total(),
                    'count'       => $paginated->count(),
                    'perPage'     => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'lastPage'    => $paginated->lastPage(),
                ],
            ],
        ], 200);
    }

    private function applySort($query, ?string $sortParam): void
    {
        $fields = $sortParam ? explode(',', $sortParam) : ['name'];

        foreach ($fields as $field) {
            $direction = 'asc';
            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = substr($field, 1);
            }

            if (! array_key_exists($field, self::SORTABLE)) {
                abort(400, "El campo de ordenamiento '{$field}' no es valido.");
            }

            $query->orderBy(self::SORTABLE[$field], $direction);
        }

        $query->orderBy('id', 'asc');
    }

    #[OA\Get(
        path: '/users/{id}',
        summary: 'Ver un usuario',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Datos del usuario')]
    )]
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'data'    => $this->toArray($user),
        ], 200);
    }

    #[OA\Post(
        path: '/users',
        summary: 'Registrar un nuevo usuario',
        description: 'Crea un usuario del personal de salud. admin_institucional solo puede otorgar roles operativos (admision, categorizacion, medico); super_admin puede otorgar cualquiera.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'role', 'organizationId', 'healthCenterId', 'unitId'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                    new OA\Property(property: 'role', type: 'string', enum: ['super_admin', 'admin_institucional', 'admision', 'categorizacion', 'medico']),
                    new OA\Property(property: 'organizationId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'healthCenterId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'unitId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado correctamente'),
            new OA\Response(response: 403, description: 'Sin permiso, o fuera del propio centro'),
            new OA\Response(response: 422, description: 'Datos invalidos, email duplicado, o rol no autorizado'),
        ]
    )]
    public function register(StoreUserRequest $request): JsonResponse
    {
        $admin = $request->user();
        $data = $request->validated();

        if ($admin->role === 'admin_institucional' && $data['healthCenterId'] !== $admin->health_center_id) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Solo puedes registrar usuarios en tu propio centro de salud.'],
            ], 403);
        }

        $user = User::create([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => $data['password'],
            'role'             => $data['role'],
            'organization_id'  => $data['organizationId'],
            'health_center_id' => $data['healthCenterId'],
            'unit_id'          => $data['unitId'],
            'is_active'        => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['user' => $this->toArray($user)],
        ], 201);
    }

    #[OA\Put(
        path: '/users/{id}',
        summary: 'Editar un usuario',
        description: 'Actualiza parcialmente un usuario. Nadie puede cambiar su propio rol. admin_institucional solo puede otorgar roles operativos.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'role', type: 'string', enum: ['super_admin', 'admin_institucional', 'admision', 'categorizacion', 'medico']),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado'),
            new OA\Response(response: 422, description: 'Datos invalidos o rol no autorizado'),
        ]
    )]
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->fill($request->validated());
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => $this->toArray($user),
        ], 200);
    }

    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Desactivar un usuario',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Usuario desactivado')]
    )]
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->is_active = false;
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => ['id' => $user->id, 'isActive' => $user->is_active],
        ], 200);
    }

    #[OA\Patch(
        path: '/users/{id}/restore',
        summary: 'Reactivar un usuario',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Usuario reactivado')]
    )]
    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);

        $user->is_active = true;
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => ['id' => $user->id, 'isActive' => $user->is_active],
        ], 200);
    }

    private function toArray(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'role'           => $user->role,
            'organizationId' => $user->organization_id,
            'healthCenterId' => $user->health_center_id,
            'unitId'         => $user->unit_id,
            'isActive'       => $user->is_active,
        ];
    }
}