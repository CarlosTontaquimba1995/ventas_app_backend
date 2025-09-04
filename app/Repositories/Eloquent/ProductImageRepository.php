<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductImage;
use App\Repositories\Interfaces\ProductImageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<ProductImage>
 */
class ProductImageRepository extends BaseRepository implements ProductImageRepositoryInterface
{
    /**
     * @var ProductImage
     */
    protected $model;

    public function __construct(ProductImage $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function getByProductId(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getPrimaryImage(int $productId): ?ProductImage
    {
        return $this->model->where('product_id', $productId)
            ->where('is_primary', true)
            ->first();
    }

    public function setAsPrimary(int $imageId): bool
    {
        return DB::transaction(function () use ($imageId) {
            $image = $this->findById($imageId);
            if (!$image) {
                return false;
            }

            // Reset primary status for all other images of this product
            $this->model->where('product_id', $image->product_id)
                ->where('id', '!=', $imageId)
                ->update(['is_primary' => false]);

            // Set this image as primary
            return $image->update(['is_primary' => true]);
        });
    }

    public function getProductImagesWithDetails(int $productId): array
    {
        $images = $this->getByProductId($productId);
        $primaryImage = $this->getPrimaryImage($productId);

        return [
            'images' => $images->map(function ($image) use ($primaryImage) {
                return [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'image_url' => $image->image_url,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at
                ];
            }),
            'primary_image' => $primaryImage ? [
                'id' => $primaryImage->id,
                'image_path' => $primaryImage->image_path,
                'image_url' => $primaryImage->image_url
            ] : null
        ];
    }

    public function updateImageOrder(int $imageId, int $sortOrder): bool
    {
        $image = $this->findById($imageId);
        if (!$image) {
            return false;
        }

        return $image->update(['sort_order' => $sortOrder]);
    }

    public function bulkUpdateImageOrder(array $orderData): bool
    {
        return DB::transaction(function () use ($orderData) {
            foreach ($orderData as $item) {
                $updated = $this->model->where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
                
                if ($updated === 0) {
                    DB::rollBack();
                    return false;
                }
            }
            return true;
        });
    }

    public function deleteByProductId(int $productId, array $excludeIds = []): bool
    {
        $query = $this->model->where('product_id', $productId);
        
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        
        return $query->delete() >= 0;
    }

    public function getImagesByProductIds(array $productIds): Collection
    {
        return $this->model->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get();
    }
    
    public function getNextSortOrder(int $productId): int
    {
        $lastImage = $this->model->where('product_id', $productId)
            ->orderBy('sort_order', 'desc')
            ->first();
            
        return $lastImage ? $lastImage->sort_order + 1 : 1;
    }
    
    public function unsetPrimaryImages(int $productId): bool
    {
        return $this->model->where('product_id', $productId)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
    
    public function unsetOtherPrimaryImages(int $productId, int $excludeImageId): bool
    {
        return $this->model->where('product_id', $productId)
            ->where('id', '!=', $excludeImageId)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
