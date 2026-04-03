<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function __construct(
        protected Category $model,
    ) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when($filters['name'] ?? null, function ($query, $name) {
                $name = trim($name);
                $query->where('name', 'ilike', "%{$name}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Category
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(array $data): Category
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }
}
