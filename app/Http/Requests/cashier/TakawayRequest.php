<?php

namespace App\Http\Requests\cashier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TakawayRequest extends FormRequest
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

            'products.*.price' => ['numeric', 'required_unless:order_pending,1,true'],

            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id'],
            'products.*.addons.*.count' => ['numeric'],

            'products.*.addons.*.price' => ['numeric', 'required_unless:order_pending,1,true'],

            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required_unless:order_pending,1,true'],
            'products.*.note' => ['sometimes'],
            'financials' => ['required_unless:order_pending,1,true', 'array'],
            'financials.*.id' => ['required_unless:order_pending,1,true', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required_unless:order_pending,1,true', 'numeric'],  
            'financials.*.description' => ['sometimes'], 
            'financials.*.transition_id' => ['sometimes'], 
            'cashier_id' => ['required_unless:order_pending,1,true', 'exists:cashiers,id'],
            'due' => ['required_unless:order_pending,1,true', 'boolean'],
            'user_id' => ['exists:users,id'],
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
