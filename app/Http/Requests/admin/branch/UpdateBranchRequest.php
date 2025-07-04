<?php

namespace App\Http\Requests\admin\branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
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
            'address' => ['required'],          
            'email' => ['email', 'required', Rule::unique('branches')->ignore($userId)],
            'phone' => ['required', Rule::unique('branches')->ignore($userId)],
            'phone_status' => ['required', 'boolean'],
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
