<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductService $service,
    ) {}

    public function index(): JsonResponse
    {
        $products = $this->service->getAll();

        return $this->success(
            ProductResource::collection($products),
            'Produtos listados com sucesso.',
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $product = $this->service->create($data);

        return $this->success(
            new ProductResource($product),
            'Produto criado com sucesso.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->service->findById($id);

        if (! $product) {
            return $this->error('Produto não encontrado.', code: 404);
        }

        return $this->success(
            new ProductResource($product),
            'Produto encontrado com sucesso.',
        );
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->service->findById($id);

        if (! $product) {
            return $this->error('Produto não encontrado.', code: 404);
        }

        $product = $this->service->update($product, $request->validated());

        return $this->success(
            new ProductResource($product),
            'Produto atualizado com sucesso.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->service->findById($id);

        if (! $product) {
            return $this->error('Produto não encontrado.', code: 404);
        }

        $this->service->delete($product);

        return $this->success(
            message: 'Produto removido com sucesso.',
        );
    }
}
