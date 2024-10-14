<?php

namespace App\Http\Requests\admin\delivery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateDeliveryRequest extends FormRequest
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
        $userId = $this->route('id');
        return [
            'f_name' => ['required'],
            'l_name' => ['required'],
            'identity_type' => ['required'],
            'identity_number' => ['required'],
            'email' => ['email', 'required', Rule::unique('users')->ignore($userId)],
            'phone' => ['required', Rule::unique('users')->ignore($userId)],
            'password' => ['required'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
