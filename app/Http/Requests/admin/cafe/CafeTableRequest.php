<?php

namespace App\Http\Requests\admin\cafe;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CafeTableRequest extends FormRequest
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
            'table_number' => ['required'],
            'location_id' => ['required', 'exists:cafe_locations,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'capacity' => ['required', 'numeric'],
            'occupied' => ['required', 'boolean'],
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
