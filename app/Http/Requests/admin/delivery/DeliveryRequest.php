<?php

namespace App\Http\Requests\admin\delivery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeliveryRequest extends FormRequest
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
            'f_name' => ['required'],
            'l_name' => ['required'],
            'identity_type' => ['required'],
            'identity_number' => ['required'],
            'email' => ['required', 'email', 'unique:deliveries'],
            'phone' => ['required', 'unique:deliveries'],
            'password' => ['required'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'phone_status' => ['required', 'boolean'],
            'chat_status' => ['required', 'boolean'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
