<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.description' => 'nullable|string',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.stock' => 'required|integer|min:0',
            'products.*.sku' => 'required|string|unique:products,sku',
            'products.*.is_active' => 'boolean',
            'products.*.is_featured' => 'boolean',
            'products.*.category_id' => 'required|exists:categories,id',
            'products.*.specifications' => 'nullable|array',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'products' => array_map(function ($product) {
                return array_merge($product, [
                    'is_active' => $product['is_active'] ?? true,
                    'is_featured' => $product['is_featured'] ?? false,
                    'specifications' => $product['specifications'] ?? [],
                ]);
            }, $this->products),
        ]);
    }
}
