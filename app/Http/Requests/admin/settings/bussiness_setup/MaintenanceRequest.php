<?php

namespace App\Http\Requests\admin\settings\bussiness_setup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MaintenanceRequest extends FormRequest
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
            'all' => ['required', 'boolean'],
            'branch' => ['required', 'boolean'],
            'customer' => ['required', 'boolean'],
            'web' => ['required', 'boolean'],
            'delivery' => ['required', 'boolean'],
            'day' => ['required', 'boolean'],
            'week' => ['required', 'boolean'],
            'until_change' => ['required', 'boolean'],
            'customize' => ['required', 'boolean'],
            'start_date' => ['date'],
            'end_date' => ['date'],
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
