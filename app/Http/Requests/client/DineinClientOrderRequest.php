<?php

namespace App\Http\Requests\client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DineinClientOrderRequest extends FormRequest
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
            'payment_method_id' => ['required', 'exists:payment_methods,id'], 
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
