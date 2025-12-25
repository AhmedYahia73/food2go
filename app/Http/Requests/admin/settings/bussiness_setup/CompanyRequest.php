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
            'time_format' => ['required', 'in:24hours,am/pm'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'currency_position' => ['required', 'in:left,right'],
            'copy_right' => ['required'],
            'time_zone' => ['required'],
            'phone2' => ['sometimes'],
            'watts' => ['sometimes'],
            'android_link' => ['sometimes'],
            'ios_link' => ['sometimes'],
            'order_online' => ['required', 'boolean'],
            'android_switch' => ['boolean'], 
            'ios_switch' => ['boolean'],
            'preparation_num_status' => ['boolean'],
            'show_map' => ['boolean'],
        ];
    }
    // migrate --path=/database/migrations/2025_02_23_124537_add_columns_to_company_infos.php
    // migrate --path=/database/migrations/2025_02_23_130221_create_menue_images_table.php
    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
