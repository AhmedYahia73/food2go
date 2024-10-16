<?php

namespace App\Http\Requests\admin\pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderRequest extends FormRequest
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
            'date' => ['date'],
            'user_id' => ['exists:users,id'],
            'branch_id' => ['exists:branches,id'],
            'customer_id' => ['exists:customers,id'],
            'amount' => ['required', 'numeric'],
            'order_status' => ['in:pending,confirmed,processing,out_for_delivery,delivered,faild_to_deliver,canceled,scheduled'],
            'order_type' => ['required'], 
            'total_tax' => ['required', 'numeric'],
            'total_discount' => ['required', 'numeric'],
            'paid_by' => ['required'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
