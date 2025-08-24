<?php

namespace App\Http\Requests\admin\pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CaptainOrderRequest extends FormRequest
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
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required'],
            'user_name' => ['required'],
            'phone' => ['required'],
            'locations.*' => ['required', 'exists:cafe_locations,id']
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
            'message'=>'validation error',
            'errors'=>$validator->errors(),
       ],400));
   }
}
