<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function __construct(
        protected Product $model,
    ) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['category', 'user'])
            ->when($filters['name'] ?? null, function ($query, $name) {
                $name = trim($name);
                $query->where('name', 'like', "%{$name}%");
            })
            ->latest()
            ->paginate($perPage);
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
