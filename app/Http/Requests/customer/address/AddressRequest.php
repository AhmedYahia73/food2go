<?php

namespace App\Http\Requests\customer\address;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddressRequest extends FormRequest
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
            'city_id' => ['required', 'exists:cities,id'],
            'zone_id' => ['required', 'exists:zones,id'],
            'address' => ['required'],
            'street' => ['required'],
            'building_num' => ['required'],
            'floor_num' => ['required'],
            'apartment' => ['required'],
            'additional_data' => ['sometimes'],
            'type' => ['required'],
            'map' => ['sometimes'], 
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
