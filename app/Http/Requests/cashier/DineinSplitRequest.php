<?php

namespace App\Http\Requests\cashier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DineinSplitRequest extends FormRequest
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
            'source' => 'sometimes',
            'cart_id' => ['array', 'required'],
            'cart_id.*' => ['exists:order_carts,id', 'required'],
            'financials' => ['array'],
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required', 'numeric'],
            'financials.*.description' => ['sometimes'], 
            'financials.*.transition_id' => ['sometimes'], 
            'table_id' => ['required', 'exists:cafe_tables,id'],
            'cashier_id' => ['sometimes', 'exists:cashiers,id'], 
            'dicount_id' => ['exists:discounts,id'],
            'free_discount' => ['numeric', 'sometimes'],
            'due_module' => ['numeric', 'sometimes'],
            'module_order_number' => ['sometimes'],

            'bundles' => ['array'],
            'bundles.*.count' => ['required', "numeric"],
            'bundles.*.id' => ['required', 'exists:bundles,id'],
            'bundles.*.variation' => ['required', 'array'],
            'bundles.*.variation.*.id' => ['required', 'exists:variation_products,id'],
            'bundles.*.variation.*.options' => ['required', 'array'],
            'bundles.*.variation.*.options.*' => ['required', 'exists:option_products,id'],

            'products' => ['array'],
            'products.*.product_id' => ['exists:products,id', 'required'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id', 'required'],
            'products.*.addons.*.count' => ['numeric', 'required'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required', 'required'],
            'products.*.note' => ['sometimes'], 

            'products.*.price' => ['numeric', 'required'],
            'products.*.addons.*.price' => ['numeric', 'required'],

            'service_fees_id' => ["exists:service_fees,id"],
            'service_fees' => ["numeric"],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
