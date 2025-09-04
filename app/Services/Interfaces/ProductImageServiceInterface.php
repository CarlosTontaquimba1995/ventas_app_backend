<?php

namespace App\Services\Interfaces;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface ProductImageServiceInterface
{
    public function getProductImages(int $productId): Collection;
    
    public function getProductImage(int $id): ?ProductImage;
    
    public function getPrimaryImage(int $productId): ?ProductImage;
    
    public function uploadProductImage(int $productId, UploadedFile $file, bool $isPrimary = false): ?ProductImage;
    
    public function setAsPrimary(int $imageId): bool;
    
    public function deleteProductImage(int $imageId): bool;
    
    public function reorderImages(array $imageIds): bool;
    
    public function updateImage(int $imageId, array $data): ?ProductImage;
}
