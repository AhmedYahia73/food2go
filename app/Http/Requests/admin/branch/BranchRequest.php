<?php

namespace App\Http\Requests\admin\branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BranchRequest extends FormRequest
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
            'address' => ['required'],
            'email' => ['required', 'email', 'unique:branches'],
            'phone' => ['required', 'unique:branches'],
            'phone_status' => ['required', 'boolean'],
            'password' => ['required'],
            'food_preparion_time' => ['required'],
            'status' => ['required'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'coverage' => ['required'],
            'branch_names' => ['required', 'array'],
            'branch_names.*.tranlation_name' => ['required'],
            'branch_names.*.branch_name' => ['required'],
            'branch_names.*.tranlation_id' => ['required'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
