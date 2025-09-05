<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
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
        $rules = [];
        
        // For adding/updating cart items
        if ($this->isMethod('post') || $this->isMethod('put')) {
            $rules['quantity'] = 'required|integer|min:1|max:100';
        }
        
        // For applying discounts
        if ($this->is('api/cart/apply-discount')) {
            $rules['discount_code'] = 'required|string|max:50';
        }
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'The quantity is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 1.',
            'quantity.max' => 'The quantity may not be greater than 100.',
            'discount_code.required' => 'The discount code is required.',
            'discount_code.string' => 'The discount code must be a string.',
            'discount_code.max' => 'The discount code may not be greater than 50 characters.',
        ];
    }
}
