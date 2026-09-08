<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePictogramRequest;
use App\Http\Requests\UpdatePictogramRequest;
use App\Models\Pictogram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class PictogramController extends Controller
{
    /**
     * Campos por los que se puede ordenar, mapeados de camelCase (API)
     * a snake_case (columna real). Un campo no ordenable produce 400
     * segun el contrato SS16.4.
     */
    private const SORTABLE = [
        'sortOrder'  => 'sort_order',
        'title'      => 'title',
        'categoryId' => 'pictogram_category_id',
        'createdAt'  => 'created_at',
    ];

    #[OA\Get(
        path: '/pictograms',
        summary: 'Listar pictogramas',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'categoryId,sortOrder')),
            new OA\Parameter(name: 'includeInactive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de pictogramas')]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Pictogram::class);

        $user = $request->user();
        $canManage = $user instanceof \App\Models\User && $user->can('update', Pictogram::class);

        $query = Pictogram::query()
            ->when($request->filled('categoryId'), fn ($q) =>
                $q->where('pictogram_category_id', $request->query('categoryId'))
            );

        // Solo quien administra pictogramas puede pedir ver los inactivos.
        // El chat del paciente nunca debe listar algo desactivado.
        $wantsInactive = $canManage && $request->boolean('includeInactive');
        if (! $wantsInactive) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            \Illuminate\Support\Facades\Validator::make(
                $request->only('search'),
                ['search' => 'min:2|max:100']
            )->validate();

            $term = trim($request->query('search'));

            $query->where(function ($q) use ($term) {
                $q->where('title', 'ilike', "%{$term}%")
                  ->orWhere('phrase', 'ilike', "%{$term}%");
            });
        }

        $this->applySort($query, $request->query('sort'));

        $pictograms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $pictograms->map(fn ($p) => $this->toArray($p)),
        ]);
    }

    /**
     * Aplica el ordenamiento pedido por el cliente, o el default del
     * contrato (categoryId,sortOrder). Siempre agrega "id" como desempate
     * final: sin eso, filas con el mismo valor de orden podrian aparecer
     * en distinto orden entre peticiones (SS16.3, determinismo obligatorio).
     */
    private function applySort($query, ?string $sortParam): void
    {
        $fields = $sortParam ? explode(',', $sortParam) : ['categoryId', 'sortOrder'];

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

    #[OA\Post(
        path: '/pictograms',
        summary: 'Crear un pictograma',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pictogramCategoryId', 'title', 'phrase', 'speechText', 'emoji', 'severity'],
                properties: [
                    new OA\Property(property: 'pictogramCategoryId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'phrase', type: 'string'),
                    new OA\Property(property: 'speechText', type: 'string'),
                    new OA\Property(property: 'emoji', type: 'string'),
                    new OA\Property(property: 'severity', type: 'string', enum: ['critical', 'warning', 'info', 'neutral']),
                    new OA\Property(property: 'isActive', type: 'boolean'),
                    new OA\Property(property: 'sortOrder', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Pictograma creado correctamente')]
    )]
    public function store(StorePictogramRequest $request): JsonResponse
    {
        $pictogram = Pictogram::create($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogram),
        ], Response::HTTP_CREATED);
    }

    #[OA\Patch(
        path: '/pictograms/{id}',
        summary: 'Actualizar parcialmente un pictograma',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'pictogramCategoryId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'phrase', type: 'string'),
                    new OA\Property(property: 'speechText', type: 'string'),
                    new OA\Property(property: 'emoji', type: 'string'),
                    new OA\Property(property: 'severity', type: 'string', enum: ['critical', 'warning', 'info', 'neutral']),
                    new OA\Property(property: 'isActive', type: 'boolean'),
                    new OA\Property(property: 'sortOrder', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Pictograma actualizado correctamente')]
    )]
    public function update(UpdatePictogramRequest $request, Pictogram $pictogram): JsonResponse
    {
        $pictogram->update($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogram),
        ]);
    }

    #[OA\Delete(
        path: '/pictograms/{id}',
        summary: 'Desactivar un pictograma (no se borra de la base de datos)',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Pictograma desactivado correctamente')]
    )]
    public function destroy(Request $request, Pictogram $pictogram): JsonResponse
    {
        $this->authorize('delete', $pictogram);

        $pictogram->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogram),
        ]);
    }

    #[OA\Patch(
        path: '/pictograms/{id}/restore',
        summary: 'Reactivar un pictograma previamente desactivado',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Pictograma reactivado correctamente')]
    )]
    public function restore(Request $request, Pictogram $pictogram): JsonResponse
    {
        $this->authorize('update', $pictogram);

        $pictogram->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogram),
        ]);
    }

    private function toArray(Pictogram $pictogram): array
    {
        return [
            'id'                  => $pictogram->id,
            'pictogramCategoryId' => $pictogram->pictogram_category_id,
            'title'               => $pictogram->title,
            'phrase'              => $pictogram->phrase,
            'speechText'          => $pictogram->speech_text,
            'emoji'               => $pictogram->emoji,
            'severity'            => $pictogram->severity->value,
            'isActive'            => $pictogram->is_active,
            'sortOrder'           => $pictogram->sort_order,
        ];
    }
}