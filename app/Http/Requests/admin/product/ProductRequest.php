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
            'excludes.*.names.*.exclude_name' => ['required'],
            'excludes.*.names.*.tranlation_id' => ['required', 'exists:translations,id'],
            'excludes.*.names.*.tranlation_name' => ['required'],
            'extra.*.names.*.extra_name' => ['required'],
            'extra.*.names.*.tranlation_id' => ['required', 'exists:translations,id'],
            'extra.*.names.*.tranlation_name' => ['required'],
            'extra.*.extra_price' => ['required', 'numeric'],
            'variations.*.names.*.name' => ['required'],
            'variations.*.names.*.tranlation_id' => ['required', 'exists:translations,id'],
            'variations.*.names.*.tranlation_name' => ['required'],
            'variations.*.type' => ['required', 'in:multiple,single'],
            'variations.*.min' => ['numeric', 'nullable'],
            'variations.*.max' => ['numeric', 'nullable'],
            'variations.*.required' => ['required', 'boolean'],
            'variations.*.options.*.names.*.name' => ['required'],
            'variations.*.options.*.names.*.tranlation_id' => ['required', 'exists:translations,id'],
            'variations.*.options.*.names.*.tranlation_name' => ['required'],
            'variations.*.options.*.price' => ['required', 'numeric'],
            'variations.*.options.*.status' => ['required', 'boolean'],
            'variations.*.options.*.names.*.name' => ['required'],
            'variations.*.options.*.names.*.name' => ['required'],
            'variations.*.options.*.points' => ['numeric', 'required'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
