<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type,
            'discount_value' => (float) $this->discount_value,
            'min_order_amount' => $this->whenNotNull($this->min_order_amount, (float) $this->min_order_amount),
            'max_uses' => $this->whenNotNull($this->max_uses, $this->max_uses),
            'max_uses_per_user' => $this->whenNotNull($this->max_uses_per_user, $this->max_uses_per_user),
            'times_used' => $this->when(isset($this->times_used), $this->times_used),
            'starts_at' => $this->whenNotNull($this->starts_at, $this->starts_at?->toDateTimeString()),
            'expires_at' => $this->whenNotNull($this->expires_at, $this->expires_at?->toDateTimeString()),
            'is_active' => (bool) $this->is_active,
            'apply_to' => $this->apply_to,
            'applicable_products' => $this->when(
                $this->apply_to === 'products' && $this->relationLoaded('products'),
                $this->products->pluck('id')
            ),
            'applicable_categories' => $this->when(
                $this->apply_to === 'categories' && $this->relationLoaded('categories'),
                $this->categories->pluck('id')
            ),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}
