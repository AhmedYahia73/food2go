<?php

namespace App\Http\Requests\admin\product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:categories,id'],
            'item_type' => ['required', 'in:online,offline,all'],
            'stock_type' => ['required', 'in:daily,unlimited,fixed'],
            'price' => ['required', 'numeric'],
            'product_time_status' => ['required', 'boolean'],
            'discount_id' => ['nullable', 'exists:discounts,id'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            'status' => ['required', 'boolean'],
            'recommended' => ['required', 'boolean'],
            'points' => ['required', 'numeric'],
            'addons.*' => ['exists:addons,id'],
            'variations.*.names.*.name' => ['required'],
            'variations.*.type' => ['required', 'in:multiple,single'],
            'variations.*.min' => ['numeric'],
            'variations.*.points' => ['numeric', 'required'],
            'variations.*.max' => ['numeric'],
            'variations.*.required' => ['required', 'boolean'],
            'variations.*.options.*.names.*.name' => ['required'],
            'variations.*.options.*.price' => ['required', 'numeric'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
