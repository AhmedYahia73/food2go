<?php

namespace App\Http\Requests\admin\coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CouponRequest extends FormRequest
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
            'type' => ['required', 'in:first_order,normal'],
            'title' => ['required'],
            'code' => ['required'],
            'start_date' => ['required', 'date'],
            'expire_date' => ['required', 'date'],
            'min_purchase' => ['numeric'],
            'max_discount_status' => ['required', 'boolean'],
            'max_discount' => ['numeric'],
            'product' => ['required', 'in:all,selected'],
            'number_usage_status' => ['required', 'in:fixed,unlimited'],
            'number_usage' => ['numeric'],
            'number_usage_user_status' => ['required', 'in:fixed,unlimited'],
            'number_usage_user' => ['numeric'],
            'discount_type' => ['required', 'in:value,percentage'],
            'discount' => ['required', 'numeric'],
            'status' => ['required', 'boolean'],
            'products_id.*' => ['exists:products,id'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
