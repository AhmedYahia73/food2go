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
            'table_id' => ['required', 'exists:cafe_tables,id'],
            'cashier_id' => ['sometimes', 'exists:cashiers,id'], 
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
