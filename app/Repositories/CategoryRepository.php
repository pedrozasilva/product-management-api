<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function __construct(
        protected Category $model,
    ) {}

    public function getAll(): Collection
    {
        return $this->model->newQuery()->latest()->get();
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
