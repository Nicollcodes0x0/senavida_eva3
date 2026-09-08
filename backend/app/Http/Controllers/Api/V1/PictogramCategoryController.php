<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePictogramCategoryRequest;
use App\Http\Requests\UpdatePictogramCategoryRequest;
use App\Models\PictogramCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class PictogramCategoryController extends Controller
{
    #[OA\Get(
        path: '/pictogram-categories',
        summary: 'Listar categorias de pictogramas',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'includeInactive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de categorias de pictogramas')]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PictogramCategory::class);

        $user = $request->user();
        $canManage = $user instanceof \App\Models\User && $user->can('update', PictogramCategory::class);

        $query = PictogramCategory::query();

        $wantsInactive = $canManage && $request->boolean('includeInactive');
        if (! $wantsInactive) {
            $query->where('is_active', true);
        }

        $categories = $query->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(fn ($c) => $this->toArray($c)),
        ]);
    }

    #[OA\Post(
        path: '/pictogram-categories',
        summary: 'Crear una categoria de pictogramas',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Emociones'),
                    new OA\Property(property: 'sortOrder', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Categoria creada correctamente')]
    )]
    public function store(StorePictogramCategoryRequest $request): JsonResponse
    {
        $category = PictogramCategory::create($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data' => $this->toArray($category),
        ], Response::HTTP_CREATED);
    }

    #[OA\Patch(
        path: '/pictogram-categories/{id}',
        summary: 'Actualizar parcialmente una categoria',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'sortOrder', type: 'integer'),
                    new OA\Property(property: 'isActive', type: 'boolean'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Categoria actualizada correctamente')]
    )]
    public function update(UpdatePictogramCategoryRequest $request, PictogramCategory $pictogramCategory): JsonResponse
    {
        $pictogramCategory->update($request->validatedForModel());

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogramCategory),
        ]);
    }

    #[OA\Delete(
        path: '/pictogram-categories/{id}',
        summary: 'Desactivar una categoria (no se borra de la base de datos)',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Categoria desactivada correctamente')]
    )]
    public function destroy(Request $request, PictogramCategory $pictogramCategory): JsonResponse
    {
        $this->authorize('delete', $pictogramCategory);

        $pictogramCategory->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogramCategory),
        ]);
    }

    #[OA\Patch(
        path: '/pictogram-categories/{id}/restore',
        summary: 'Reactivar una categoria previamente desactivada',
        tags: ['Pictogramas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OA\Response(response: 200, description: 'Categoria reactivada correctamente')]
    )]
    public function restore(Request $request, PictogramCategory $pictogramCategory): JsonResponse
    {
        $this->authorize('update', $pictogramCategory);

        $pictogramCategory->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($pictogramCategory),
        ]);
    }

    private function toArray(PictogramCategory $category): array
    {
        return [
            'id'        => $category->id,
            'name'      => $category->name,
            'sortOrder' => $category->sort_order,
            'isActive'  => $category->is_active,
        ];
    }
}