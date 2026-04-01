<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;

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
        return $this->repository->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        return $this->repository->update($product, $data);
    }

    public function delete(Product $product): bool
    {
        return $this->repository->delete($product);
    }
}
