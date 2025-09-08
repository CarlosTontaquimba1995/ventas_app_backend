<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
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
            ->with('category')
            ->limit($limit)
            ->get();
    }

    public function getNewArrivals(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->active()
            ->with('category')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)
            ->with('category')
            ->first();
    }
    
    public function findActiveById(int $id): ?Product
    {
        return $this->model->active()
            ->with('category')
            ->find($id);
    }

    public function search(string $query, int $perPage = 10, string $sortBy = 'created_at', string $sortOrder = 'desc', int $page = 1): LengthAwarePaginator
    {
        return $this->model->query()
            ->with('category')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('description', 'ilike', "%{$query}%")
                    ->orWhere('sku', 'ilike', "%{$query}%");
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getByCategory(int $categoryId, int $perPage = 10, string $sortBy = 'name', string $sortOrder = 'asc', int $page = 1): LengthAwarePaginator
    {
        return $this->model->query()
            ->with('category')
            ->where('category_id', $categoryId)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        return $this->model->query()
            ->with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
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
            ->with('category')
            ->latest()
            ->paginate($perPage);
    }
    
    public function getLowStockProducts(int $minStock = 5, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('stock', '<=', $minStock)
            ->where('stock', '>', 0) // Only include products with some stock
            ->with('category')
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
            // Delete the product
            return (bool) $product->delete();
        } catch (\Exception $e) {
            return false;
        }
    }
}
