<?php

namespace App\Http\Requests\cashier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DineinOrderRequest extends FormRequest
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
     
            'amount' => ['required_unless:order_pending,1,true', 'numeric'], 
            'total_tax' => ['required_unless:order_pending,1,true', 'numeric'],
            'total_discount' => ['required_unless:order_pending,1,true', 'numeric'], 
            'products' => ['required_unless:order_pending,1,true', 'array'],
            'products.*.product_id' => ['exists:products,id', 'required_unless:order_pending,1,true'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id', 'required_unless:order_pending,1,true'],
            'products.*.addons.*.count' => ['numeric', 'required_unless:order_pending,1,true'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required', 'required_unless:order_pending,1,true'],
            'products.*.note' => ['sometimes'], 

            'products.*.price' => ['numeric', 'required_unless:order_pending,1,true'],
            'products.*.addons.*.price' => ['numeric', 'required_unless:order_pending,1,true'],


            'financials' => ['array'],
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required', 'numeric'],
            'financials.*.description' => ['sometimes'],  
            'financials.*.transition_id' => ['sometimes'], 
            'cashier_id' => ['required', 'exists:cashiers,id'],
            'user_id' => ['exists:users,id'],
            'dicount_id' => ['exists:discounts,id'],
            'due' => ['required_unless:order_pending,1,true', 'boolean'],
            'source' => 'sometimes', 
            'table_id' => ['required', 'exists:cafe_tables,id'],

        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
