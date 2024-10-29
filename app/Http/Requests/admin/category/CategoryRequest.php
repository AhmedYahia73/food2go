<?php

namespace App\Http\Requests\admin\category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategoryRequest extends FormRequest
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
            'category_id' => ['exists:categories,id', 'nullable'],
            'status' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'priority' => ['required', 'integer'],
            'addons_id.*' => ['exists:addons,id'],
            'category_names.*.tranlation_name' => ['required'],
            'category_names.*.category_name' => ['required'],
            'category_names.*.tranlation_id' => ['required'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
