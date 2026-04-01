<?php

namespace App\Services;

use App\Events\Product\ProductCreated;
use App\Events\Product\ProductDeleted;
use App\Events\Product\ProductUpdated;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;

class ProductService
{
    public function __construct(
        protected ProductRepository $repository,
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function findById(int $id): ?Product
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Product
    {
        $product = $this->repository->create($data);

        $user = User::find($data['user_id']);

        ProductCreated::dispatch($product, $user, Request::ip(), Request::userAgent());

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $oldValues = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock_quantity' => $product->stock_quantity,
            'is_active' => $product->is_active,
            'category_id' => $product->category_id,
        ];

        $product = $this->repository->update($product, $data);

        ProductUpdated::dispatch($product, $product->user, $oldValues, Request::ip(), Request::userAgent());

        return $product;
    }

    public function delete(Product $product): bool
    {
        $user = $product->user;

        ProductDeleted::dispatch($product, $user, Request::ip(), Request::userAgent());

        return $this->repository->delete($product);
    }
}
