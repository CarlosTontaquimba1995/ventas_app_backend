<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'order' => $this->order,
            'has_children' => $this->whenLoaded('children', function() {
                return $this->children->isNotEmpty();
            }, false),
            'children' => $this->whenLoaded('children', function() {
                return CategoryResource::collection($this->children);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // If we want to include the parent category
        if ($this->relationLoaded('parent') && $this->parent) {
            $data['parent'] = new CategoryResource($this->parent);
        }

        return $data;
    }
}
