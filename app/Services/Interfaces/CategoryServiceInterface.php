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
     */
    public function createCategory(array $data): Category;

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
}
