<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscountRequest extends FormRequest
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
        return [
            'code' => 'required|string|max:50',
            'order_id' => 'required|exists:orders,id',
            'offer_code' => 'nullable|string|max:50',
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
            'code.required' => 'The discount code is required.',
            'code.string' => 'The discount code must be a string.',
            'code.max' => 'The discount code may not be greater than 50 characters.',
            'order_id.required' => 'The order ID is required.',
            'order_id.exists' => 'The selected order is invalid.',
            'offer_code.string' => 'The offer code must be a string.',
            'offer_code.max' => 'The offer code may not be greater than 50 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // If we're validating a discount code (from validateCode method)
        if ($this->is('api/v1/discounts/validate')) {
            $this->merge([
                'code' => $this->code,
            ]);
        }
        
        // If we're applying a discount (from apply method)
        if ($this->is('api/v1/discounts/apply')) {
            $this->merge([
                'order_id' => $this->order_id,
                'offer_code' => $this->offer_code ?? null,
            ]);
        }
    }
}
