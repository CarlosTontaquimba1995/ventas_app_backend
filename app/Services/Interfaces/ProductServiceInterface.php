<?php

namespace App\Services\Interfaces;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    public function getFeatured(int $limit = 5): Collection;
    
    public function getNewArrivals(int $limit = 5): Collection;
    
    public function getBySlug(string $slug): ?Product;
    
    public function search(string $query, int $perPage = 10): LengthAwarePaginator;
    
    public function getByCategory(int $categoryId, int $perPage = 10, int $page = 1): LengthAwarePaginator;
    
    public function getRelatedProducts(Product $product, int $limit = 4): Collection;
    
    public function createProduct(array $data): Product;
    
    public function updateProduct(int $id, array $data): bool;
    
    public function updateProductInventory(int $productId, int $quantity, string $action = 'add'): bool;
    
    public function deleteProduct(int $id): bool;
    
    public function getProductById(int $id): ?Product;
    
    public function updateProductStatus(int $id, bool $isActive): bool;
    
    public function updateProductFeaturedStatus(int $id, bool $isFeatured): bool;
    
    public function getProductsByStatus(bool $isActive, int $perPage = 10): LengthAwarePaginator;
    
    public function getProductsStockAlert(int $minStock = 5, int $perPage = 10): LengthAwarePaginator;
}
