<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => number_format($this->price, 2, '.', ''),
            'compare_price' => $this->sale_price ? number_format($this->sale_price, 2, '.', '') : null,
            'stock' => $this->stock,
            'sku' => $this->sku,
            'barcode' => $this->barcode ?? '',
            'is_active' => (bool)$this->is_active,
            'is_featured' => (bool)$this->is_featured,
            'has_variants' => false, // Assuming no variants by default
            'image' => $this->image ?? 'https://via.placeholder.com/300',
            'specifications' => $this->specifications ?? (object)[], // Assuming specifications is a JSON column
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
            'final_price' => number_format($this->sale_price ?? $this->price, 2, '.', ''),
            'in_stock' => $this->stock > 0,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
