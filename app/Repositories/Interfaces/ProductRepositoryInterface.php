<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<Product>
 */
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function getFeatured(int $limit = 5): Collection;
    
    public function getNewArrivals(int $limit = 5): Collection;
    
    public function getBySlug(string $slug): ?Product;
    
    public function search(string $query, int $perPage = 10): LengthAwarePaginator;
    
    public function getByCategory(int $categoryId, int $perPage = 10): LengthAwarePaginator;
    
    public function getRelatedProducts(Product $product, int $limit = 4): Collection;
    
    public function getFinalPrice(Product $product): float;
    
    public function updateStock(int $productId, int $quantity, bool $increment = false): bool;
    
    public function getActiveProducts(array $columns = ['*'], array $relations = []): Collection;

    public function findActiveById(int $id): ?Product;

    public function getByStatus(bool $isActive, int $perPage = 10): LengthAwarePaginator;
    
    public function getLowStockProducts(int $minStock = 5, int $perPage = 10): LengthAwarePaginator;
    
    /**
     * Get paginated products with category relationship
     *
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedWithCategory(int $perPage = 15, int $page = 1): LengthAwarePaginator;
    
    /**
     * Get paginated products by category ID
     *
     * @param int $categoryId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedByCategory(int $categoryId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    
    /**
     * Create multiple products in bulk
     *
     * @param array $productsData
     * @return array
     */
    public function createBulk(array $productsData): array;
    
    public function deleteById(int $id): bool;
}
