<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\ListCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CategoryService $service,
    ) {}

    public function index(ListCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $categories = $this->service->paginate($data);

        return $this->paginatedSuccess(
            $categories,
            CategoryResource::class,
            'Categorias listadas com sucesso.',
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->create($request->validated());

        return $this->success(
            new CategoryResource($category),
            'Categoria criada com sucesso.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->service->findById($id);

        if (! $category) {
            return $this->error('Categoria não encontrada.', code: 404);
        }

        return $this->success(
            new CategoryResource($category),
            'Categoria encontrada com sucesso.',
        );
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->service->findById($id);

        if (! $category) {
            return $this->error('Categoria não encontrada.', code: 404);
        }

        $category = $this->service->update($category, $request->validated());

        return $this->success(
            new CategoryResource($category),
            'Categoria atualizada com sucesso.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->service->findById($id);

        if (! $category) {
            return $this->error('Categoria não encontrada.', code: 404);
        }

        $this->service->delete($category);

        return $this->success(
            message: 'Categoria removida com sucesso.',
        );
    }
}
