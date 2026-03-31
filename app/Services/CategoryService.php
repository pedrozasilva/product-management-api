<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $repository,
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function findById(int $id): ?Category
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Category
    {
        return $this->repository->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        return $this->repository->update($category, $data);
    }

    public function delete(Category $category): bool
    {
        return $this->repository->delete($category);
    }
}
