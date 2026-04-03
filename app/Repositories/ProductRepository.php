<?php

namespace App\Repositories;

use App\Models\Product;
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
                $query->where('name', 'ilike', "%{$name}%");
            })
            ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when(isset($filters['price_min']), function ($query) use ($filters) {
                $query->where('price', '>=', $filters['price_min']);
            })
            ->when(isset($filters['price_max']), function ($query) use ($filters) {
                $query->where('price', '<=', $filters['price_max']);
            })
            ->when(isset($filters['in_stock']), function ($query) use ($filters) {
                $filters['in_stock']
                    ? $query->where('stock_quantity', '>', 0)
                    : $query->where('stock_quantity', '<=', 0);
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
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
