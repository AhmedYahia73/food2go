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
            'products.*.product_id' => ['exists:products,id', 'required'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required'],
            'products.*.note' => ['sometimes'],
            'financials' => ['required', 'array'],
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required', 'numeric'], 
            'cashier_id' => ['required', 'exists:cashiers,id'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
