<?php

namespace App\Http\Requests\admin\banner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BannerRequest extends FormRequest
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
            'order' => ['required', 'numeric'],
            'images.*.translation_id' => ['required', 'exists:translations,id'],
            'images.*.image' => ['required'],
            'category_id' => ['exists:categories,id', 'nullable'],
            'product_id' => ['exists:products,id', 'nullable'],
            'deal_id' => ['exists:deals,id', 'nullable'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
