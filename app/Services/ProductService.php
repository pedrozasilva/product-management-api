<?php

namespace App\Services;

use App\Events\Product\ProductCreated;
use App\Events\Product\ProductDeleted;
use App\Events\Product\ProductUpdated;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        protected ProductRepository $repository,
    ) {}

    public function paginate(array $data): LengthAwarePaginator
    {
        $perPage = min($data['per_page'] ?? 20, 100);
        return $this->repository->paginate($data, $perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->repository->findById($id);
    }

    public function create(array $data, string $ip, ?string $userAgent): Product
    {
        $product = $this->repository->create($data);

        $user = User::find($data['user_id']);

        ProductCreated::dispatch($product, $user, $ip, $userAgent);

        return $product;
    }

    public function update(Product $product, array $data, string $ip, ?string $userAgent): Product
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

        ProductUpdated::dispatch($product, $product->user, $oldValues, $ip, $userAgent);

        return $product;
    }

    public function delete(Product $product, string $ip, ?string $userAgent): bool
    {
        $user = $product->user;

        ProductDeleted::dispatch($product, $user, $ip, $userAgent);

        return $this->repository->delete($product);
    }
}
