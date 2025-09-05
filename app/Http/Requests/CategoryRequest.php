<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category');
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId)
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($categoryId && $categoryId == $value) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'order' => 'nullable|integer|min:0',
            'children' => 'nullable|array',
            'children.*.name' => 'required|string|max:255',
            'children.*.description' => 'nullable|string',
            'children.*.image' => 'nullable|string|max:255',
            'children.*.is_active' => 'boolean',
            'children.*.order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.string' => 'The category name must be a string.',
            'name.max' => 'The category name may not be greater than 255 characters.',
            'name.unique' => 'A category with this name already exists.',
            'description.string' => 'The description must be a string.',
            'image.string' => 'The image path must be a string.',
            'image.max' => 'The image path may not be greater than 255 characters.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }
}
