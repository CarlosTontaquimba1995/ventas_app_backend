<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Category>
 */
class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    /**
     * @var Category
     */
    protected $model;

    public function __construct(Category $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->active()->get();
    }

    public function getFeatured(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->active()
            ->withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getProductsByCategory(int $categoryId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->findOrFail($categoryId)
            ->products()
            ->active()
            ->with(['category', 'images'])
            ->paginate($perPage);
    }
    
    public function getCategoryWithProducts(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)
            ->with(['products' => function($query) {
                $query->active()->with(['category', 'images']);
            }])
            ->first();
    }
    
    public function getCategoriesWithProductCount(): Collection
    {
        return $this->model->withCount(['products' => function($query) {
            $query->active();
        }])
        ->active()
        ->get();
    }
    
    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->withCount('products')
            ->paginate($perPage);
    }
    
    public function getCategoryTree(int $parentId = null): Collection
    {
        return $this->model->where('parent_id', $parentId)
            ->with(['children' => function($query) {
                $query->withCount('products');
            }])
            ->withCount('products')
            ->get();
    }
}
