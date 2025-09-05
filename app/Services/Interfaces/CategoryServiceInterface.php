<?php

namespace App\Services\Interfaces;

use Illuminate\Support\Collection;
use App\Models\Category;

interface CategoryServiceInterface
{
    /**
     * Get all active categories
     */
    public function getAllActiveCategories(): Collection;

    /**
     * Get category by ID
     */
    public function getCategoryById(int $id): ?Category;

    /**
     * Create a new category
     * 
     * @param array $data Category data including optional 'children' array for nested categories
     * @param bool $withChildren Whether to create child categories if they exist in the data
     * @return Category
     */
    public function createCategory(array $data, bool $withChildren = false): Category;

    /**
     * Update a category
     */
    public function updateCategory(int $id, array $data): ?Category;

    /**
     * Delete a category
     */
    public function deleteCategory(int $id): bool;

    /**
     * Get categories with product count
     */
    public function getCategoriesWithProductCount(): Collection;

    /**
     * Get category by slug
     */
    public function getCategoryBySlug(string $slug): ?Category;

    /**
     * Get root categories (categories without parent)
     */
    public function getRootCategories(): Collection;

    /**
     * Get categories by parent ID
     */
    public function getCategoriesByParent(?int $parentId): Collection;

    /**
     * Get category with its children hierarchy
     */
    public function getCategoryWithChildren(int $id): ?Category;
}
