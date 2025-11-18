<?php

namespace App\Http\Requests\cashier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
            'total_tax' => ['required', 'numeric'],
            'total_discount' => ['required', 'numeric'],
            'notes' => 'sometimes',
            'source' => 'sometimes',
            'products' => ['required_unless:order_pending,1,true', 'array'],
            'products.*.product_id' => ['exists:products,id', 'required_unless:order_pending,1,true'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id', 'required_unless:order_pending,1,true'],
            'products.*.addons.*.count' => ['numeric', 'required_unless:order_pending,1,true'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required_unless:order_pending,1,true'],
            'products.*.note' => ['sometimes'],

            'products.*.price' => ['numeric', 'required_unless:order_pending,1,true'],
            'products.*.addons.*.price' => ['numeric', 'required_unless:order_pending,1,true'],

            'financials' => ['array'],
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required', 'numeric'],
            'financials.*.description' => ['sometimes'],  
            'financials.*.transition_id' => ['sometimes'], 
            'cash_with_delivery' => ['boolean'],
            'cashier_id' => ['required', 'exists:cashiers,id'],
            'user_id' => 'required|exists:users,id',
            'address_id' => 'required|exists:addresses,id',
            'user_id' => ['exists:users,id', 'required_unless:order_pending,1,true'],
            'dicount_id' => ['exists:discounts,id'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
