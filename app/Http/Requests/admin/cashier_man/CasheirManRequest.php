<?php

namespace App\Http\Requests\admin\cashier_man;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CasheirManRequest extends FormRequest
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
            'user_name' => ['required'],
            'status' => ['required', 'boolean'],
            'take_away' => ['required', 'boolean'],
            'dine_in' => ['required', 'boolean'],
            'delivery' => ['required', 'boolean'],
            'real_order' => ['required', 'boolean'],
            'my_id' => ['required'],
            'discount_perimission' => ['required', 'boolean'],
            'online_order' => ['required', 'boolean'],
            'void_order' => ['required', 'boolean'],
            'report' => ['required', 'in:unactive,financial,all'],
            'free_discount' => ['sometimes', "boolean"],
            'manger' => ['sometimes', "boolean"],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
