<?php

namespace App\Repositories\Interfaces;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ProductImage>
 */
interface ProductImageRepositoryInterface extends BaseRepositoryInterface
{
    public function getByProductId(int $productId): Collection;
    
    public function getPrimaryImage(int $productId): ?ProductImage;
    
    public function setAsPrimary(int $imageId): bool;
    
    public function getProductImagesWithDetails(int $productId): array;
    
    public function updateImageOrder(int $imageId, int $sortOrder): bool;
    
    public function bulkUpdateImageOrder(array $orderData): bool;
    
    public function deleteByProductId(int $productId, array $excludeIds = []): bool;
    
    public function getImagesByProductIds(array $productIds): Collection;
    
    public function getNextSortOrder(int $productId): int;
    
    public function unsetPrimaryImages(int $productId): bool;
    
    public function unsetOtherPrimaryImages(int $productId, int $excludeImageId): bool;
}
