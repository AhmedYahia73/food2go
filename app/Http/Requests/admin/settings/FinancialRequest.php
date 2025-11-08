<?php

namespace App\Http\Requests\admin\settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FinancialRequest extends FormRequest
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
            'name' => ['required'],
            'details' => ['required'],
            'discount' => ['required', 'boolean'],
            'balance' => ['required', 'numeric'],
            'description_status' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
            'branch_id' => ['required', 'array'],
            'branch_id.*' => ['required', 'exists:branches,id'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
