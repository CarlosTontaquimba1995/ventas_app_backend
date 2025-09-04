<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Product>
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    /**
     * @var Product
     */
    protected $model;

    public function __construct(Product $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function getFeatured(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->active()
            ->where('is_featured', true)
            ->with(['category', 'images'])
            ->limit($limit)
            ->get();
    }

    public function getNewArrivals(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->active()
            ->with(['category', 'images'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)
            ->with(['category', 'images'])
            ->first();
    }

    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with(['category', 'images'])
            ->active()
            ->paginate($perPage);
    }

    public function getByCategory(int $categoryId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('category_id', $categoryId)
            ->with(['category', 'images'])
            ->active()
            ->paginate($perPage);
    }

    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        return $this->model->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['category', 'images'])
            ->active()
            ->limit($limit)
            ->get();
    }
    
    public function getMainImage(Product $product)
    {
        return $product->images()
            ->where('is_primary', true)
            ->first() ?? $product->images()->first();
    }
    
    public function getFinalPrice(Product $product): float
    {
        return (float) ($product->sale_price ?? $product->price);
    }
    
    public function updateStock(int $productId, int $quantity, bool $increment = false): bool
    {
        $product = $this->findById($productId);
        
        if (!$product) {
            return false;
        }
        
        if ($increment) {
            $product->increment('stock', $quantity);
        } else {
            if ($product->stock < $quantity) {
                return false;
            }
            $product->decrement('stock', $quantity);
        }
        
        return true;
    }
    
    public function getActiveProducts(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model
            ->select($columns)
            ->with($relations)
            ->where('is_active', true)
            ->get();
    }
    
    public function getByStatus(bool $isActive, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('is_active', $isActive)
            ->with(['category', 'images'])
            ->latest()
            ->paginate($perPage);
    }
    
    public function getLowStockProducts(int $minStock = 5, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('stock', '<=', $minStock)
            ->where('stock', '>', 0) // Only include products with some stock
            ->with(['category', 'images'])
            ->orderBy('stock', 'asc')
            ->paginate($perPage);
    }
    
    public function deleteById(int $id): bool
    {
        $product = $this->findById($id);
        if (!$product) {
            return false;
        }
        
        try {
            // Delete associated images
            $product->images()->delete();
            
            // Delete the product
            return (bool) $product->delete();
        } catch (\Exception $e) {
            return false;
        }
    }
}
