<?php

namespace App\Http\Requests\admin\admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
            'name' => ['required'],
            'identity_type' => ['required'],
            'email' => ['email', 'required', Rule::unique('admins')->ignore($userId)],
            'phone' => ['required', Rule::unique('admins')->ignore($userId)],
            'identity_number' => ['required', 'numeric'],  
            'user_position_id' => ['nullable', 'exists:user_positions,id'],   
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
