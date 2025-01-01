<?php

namespace App\Http\Requests\admin\settings\bussiness_setup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
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
            'name' => ['required'],
            'country' => ['required'],
            'phone' => ['required'],
            'email' => ['required', 'email'],
            'address' => ['required'],
            'time_zone' => ['required'],
            'time_format' => ['required', 'in:24hours,am/pm'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'currency_position' => ['required', 'in:left,right'],
            'copy_right' => ['required'],
            'time_zone' => ['required'],
            'country' => ['required'],
            'logo' => ['required'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
