<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use Illuminate\Support\Collection;

class CategoryService implements CategoryServiceInterface
{
    protected $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllActiveCategories(): Collection
    {
        return $this->categoryRepository->getAllActive();
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function createCategory(array $data): Category
    {
        $category = $this->categoryRepository->create($data);
        if (!$category) {
            throw new \RuntimeException('Failed to create category');
        }
        return $category;
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        $category = $this->getCategoryById($id);
        if (!$category) {
            return null;
        }
        
        try {
            $updated = $this->categoryRepository->update($id, $data);
            return $updated ? $this->getCategoryById($id) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->getCategoryById($id);
        if (!$category) {
            return false;
        }
        
        try {
            return $this->categoryRepository->deleteById($id);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCategoriesWithProductCount(): Collection
    {
        return $this->categoryRepository->getCategoriesWithProductCount();
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->getBySlug($slug);
    }
}
