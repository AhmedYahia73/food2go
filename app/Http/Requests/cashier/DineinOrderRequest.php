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
            'amount' => ['required', 'numeric'],
            
            'products' => ['required_if:order_pending,false', 'array'],
            'products.*.product_id' => ['exists:products,id', 'required_if:order_pending,false'],

            'products.*.price' => ['numeric', 'required'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id', 'required'],
            'products.*.extra_id.*' => ['exists:extra_products,id', 'required'],
            'products.*.addons.*.addon_id' => ['exists:addons,id', 'required'],
            'products.*.addons.*.count' => ['numeric', 'required'],
            'products.*.addons.*.price' => ['numeric', 'required'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id', 'required'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id', 'required'],
            'products.*.count' => ['numeric', 'required'],
            'products.*.note' => ['sometimes'],

            'financials' => ['required', 'array'],
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required', 'numeric'],
            'financials.*.description' => ['sometimes'], 
            'financials.*.transition_id' => ['sometimes'], 
            'table_id' => ['required', 'exists:cafe_tables,id'],
            'total_tax' => ['required', 'numeric'],
            'total_discount' => ['required', 'numeric'],
            'cashier_id' => ['sometimes', 'exists:cashiers,id'], 
            'source' => 'sometimes', 
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
