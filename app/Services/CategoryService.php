<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
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

    public function createCategory(array $data, bool $withChildren = false): Category
    {
        // Extract children if they exist
        $children = $data['children'] ?? [];
        unset($data['children']);

        // Create the parent category
        $category = $this->categoryRepository->create($data);

        if (!$category) {
            throw new \RuntimeException('Failed to create category');
        }

        // If there are children and we're supposed to create them
        if ($withChildren && !empty($children)) {
            $this->createChildCategories($category->id, $children);
        }

        // Reload the category with its children
        return $category->load('children');
    }

    /**
     * Create child categories for a parent category
     *
     * @param int $parentId
     * @param array $children
     * @return void
     */
    protected function createChildCategories(int $parentId, array $children): void
    {
        foreach ($children as $childData) {
            try {
                // Ensure parent category exists
                $parent = $this->getCategoryById($parentId);
                if (!$parent) {
                    throw new \RuntimeException("Parent category with ID {$parentId} not found.");
                }

                // Set parent_id and ensure slug is not set (let the model handle it)
                $childData['parent_id'] = $parentId;
                unset($childData['slug']); // Let the model generate the slug

                // Extract grandchildren before creating the child
                $grandChildren = $childData['children'] ?? [];
                unset($childData['children']);

                // Create the child category
                $child = $this->categoryRepository->create($childData);

                // Recursively create grandchildren if they exist
                if (!empty($grandChildren)) {
                    $this->createChildCategories($child->id, $grandChildren);
                }
            } catch (\Exception $e) {
                // Log the error and continue with next child
                Log::error('Error creating child category: ' . $e->getMessage(), [
                    'parent_id' => $parentId,
                    'child_data' => $childData,
                    'error' => $e->getTraceAsString()
                ]);
                throw $e; // Re-throw to be handled by the parent try-catch
            }
        }
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

    public function getRootCategories(): Collection
    {
        return $this->categoryRepository->getCategoryTree(null);
    }

    public function getCategoriesByParent(?int $parentId): Collection
    {
        return $this->categoryRepository->getCategoryTree($parentId);
    }

    public function getCategoryWithChildren(int $id): ?Category
    {
        $category = $this->getCategoryById($id);

        if (!$category) {
            return null;
        }

        // Eager load the children relationship
        $category->load(['children' => function ($query) {
            $query->orderBy('order')->with('children');
        }]);

        return $category;
    }
}
