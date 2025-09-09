<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\Interfaces\ProductServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService extends BaseService implements ProductServiceInterface
{
    protected $productRepository;
    protected $categoryRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        $categoryRepository = null
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function getFeatured(int $limit = 5): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    public function getNewArrivals(int $limit = 5): Collection
    {
        return $this->productRepository->getNewArrivals($limit);
    }

    public function getBySlug(string $slug): ?Product
    {
        return $this->productRepository->getBySlug($slug);
    }

    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        return $this->productRepository->search($query, $perPage);
    }

    public function getByCategory(int $categoryId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->productRepository->getByCategory($categoryId, $perPage, 'id', 'asc', $page);
    }

    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        return $this->productRepository->getRelatedProducts($product, $limit);
    }

    public function createProduct(array $data): Product
    {
        $data['slug'] = $this->generateSlug($data['name']);
        $data['sku'] = $this->generateSku();
        
        return $this->productRepository->create($data);
    }

    public function updateProduct(int $id, array $data): bool
    {
        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        return $this->productRepository->update($id, $data);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->deleteById($id);
    }
    
    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }
    
    public function updateProductStatus(int $id, bool $isActive): bool
    {
        return $this->productRepository->update($id, ['is_active' => $isActive]);
    }
    
    public function updateProductFeaturedStatus(int $id, bool $isFeatured): bool
    {
        return $this->productRepository->update($id, ['is_featured' => $isFeatured]);
    }
    
    public function getProductsByStatus(bool $isActive, int $perPage = 10): LengthAwarePaginator
    {
        return $this->productRepository->getByStatus($isActive, $perPage);
    }
    
    public function getProductsStockAlert(int $minStock = 5, int $perPage = 10): LengthAwarePaginator
    {
        return $this->productRepository->getLowStockProducts($minStock, $perPage);
    }
    
    /**
     * Get paginated products with category relationship
     *
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedProductsWithCategory(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->productRepository->getPaginatedWithCategory($perPage, $page);
    }
    
    /**
     * Get paginated products by category ID
     *
     * @param int $categoryId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedProductsByCategory(int $categoryId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->productRepository->getPaginatedByCategory($categoryId, $perPage, $page);
    }
    
    /**
     * Create multiple products in bulk
     *
     * @param array $productsData
     * @return Collection
     */
    public function bulkCreate(array $productsData): Collection
    {
        // Prepare the products data
        $preparedProducts = array_map(function ($productData) {
            // Generate slug from name if not provided
            if (!isset($productData['slug'])) {
                $productData['slug'] = Str::slug($productData['name']);
            }
            
            // Convert specifications to JSON if it's an array
            if (isset($productData['specifications']) && is_array($productData['specifications'])) {
                $productData['specifications'] = json_encode($productData['specifications']);
            }
            
            return $productData;
        }, $productsData);
        
        // Use the repository's bulk create method
        $products = $this->productRepository->createBulk($preparedProducts);
        
        return collect($products);
    }

    public function updateProductInventory(int $productId, int $quantity, string $action = 'add'): bool
    {
        $product = $this->productRepository->findById($productId);
        
        if (!$product) {
            return false;
        }
        
        switch ($action) {
            case 'add':
                $product->increment('stock', $quantity);
                break;
            case 'subtract':
                $product->decrement('stock', $quantity);
                break;
            case 'set':
                $product->update(['stock' => $quantity]);
                break;
        }
        
        return true;
    }

    protected function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = Product::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }
    
    protected function generateSku(): string
    {
        return 'SKU-' . strtoupper(Str::random(8));
    }
}
