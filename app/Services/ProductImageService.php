<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\Interfaces\ProductImageRepositoryInterface;
use App\Services\Interfaces\ProductImageServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductImageService extends BaseService implements ProductImageServiceInterface
{
    protected $productImageRepository;

    public function __construct(ProductImageRepositoryInterface $productImageRepository)
    {
        $this->productImageRepository = $productImageRepository;
    }

    public function getProductImages(int $productId): Collection
    {
        return $this->productImageRepository->getByProductId($productId);
    }

    public function getProductImage(int $id): ?ProductImage
    {
        return $this->productImageRepository->findById($id);
    }

    public function getPrimaryImage(int $productId): ?ProductImage
    {
        return $this->productImageRepository->getPrimaryImage($productId);
    }

    public function uploadProductImage(int $productId, UploadedFile $file, bool $isPrimary = false): ?ProductImage
    {
        $path = $file->store('products/' . $productId, 'public');
        
        $data = [
            'product_id' => $productId,
            'image_path' => $path,
            'is_primary' => $isPrimary,
            'sort_order' => $this->productImageRepository->getNextSortOrder($productId)
        ];
        
        $image = $this->productImageRepository->create($data);
        
        // If this is set as primary, ensure no other images are marked as primary
        if ($isPrimary) {
            $this->productImageRepository->unsetOtherPrimaryImages($productId, $image->id);
        }
        
        return $image;
    }

    public function setAsPrimary(int $imageId): bool
    {
        $image = $this->getProductImage($imageId);
        if (!$image) {
            return false;
        }
        
        // Unset any existing primary image
        $this->productImageRepository->unsetPrimaryImages($image->product_id);
        
        // Set this image as primary
        return $this->productImageRepository->update($imageId, ['is_primary' => true]);
    }

    public function deleteProductImage(int $imageId): bool
    {
        $image = $this->getProductImage($imageId);
        if (!$image) {
            return false;
        }
        
        try {
            // Delete the file from storage
            Storage::disk('public')->delete($image->image_path);
            
            // Delete the record
            return $this->productImageRepository->deleteById($imageId);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function reorderImages(array $imageIds): bool
    {
        try {
            foreach ($imageIds as $index => $id) {
                $this->productImageRepository->update($id, ['sort_order' => $index + 1]);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateImage(int $imageId, array $data): ?ProductImage
    {
        $updated = $this->productImageRepository->update($imageId, $data);
        
        // If this image is being set as primary, unset other primary images
        if (isset($data['is_primary']) && $data['is_primary']) {
            $image = $this->getProductImage($imageId);
            if ($image) {
                $this->productImageRepository->unsetOtherPrimaryImages($image->product_id, $imageId);
            }
        }
        
        return $updated ? $this->getProductImage($imageId) : null;
    }
}
