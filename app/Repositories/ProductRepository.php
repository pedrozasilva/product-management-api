<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function __construct(
        protected Product $model,
    ) {}

    public function getAll(): Collection
    {
        return $this->model->newQuery()->with(['category', 'user'])->latest()->get();
    }

    public function findById(int $id): ?Product
    {
        return $this->model->newQuery()->with(['category', 'user'])->find($id);
    }

    public function create(array $data): Product
    {
        $product = $this->model->newQuery()->create($data);

        return $product->load(['category', 'user']);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['category', 'user']);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
