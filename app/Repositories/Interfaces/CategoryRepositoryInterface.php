<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<Category>
 */
interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllActive(): Collection;
    
    public function getFeatured(int $limit = 5): Collection;
    
    public function getBySlug(string $slug): ?Category;
    
    public function getProductsByCategory(int $categoryId, int $perPage = 10): LengthAwarePaginator;
    
    public function getCategoryWithProducts(string $slug): ?Category;
    
    public function getCategoriesWithProductCount(): Collection;
    
    public function search(string $query, int $perPage = 10): LengthAwarePaginator;
    
    public function getCategoryTree(int $parentId = null): Collection;
}
