<?php

namespace App\Http\Requests\customer\order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderFromCartRequest extends FormRequest
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
            'date' => ['regex:/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/'],
            'branch_id' => ['exists:branches,id', 'nullable'],
            'payment_method_id' => ['exists:payment_methods,id'],
            'address_id' => ['exists:addresses,id', 'nullable'],
            'order_type' => ['in:take_away,dine_in,delivery,car_slow'],
            'sechedule_slot_id' => ['exists:schedule_slots,id'],
            'coupon_id' => ['exists:coupons,id'],
            'service_fees_id' => ["exists:service_fees,id"],
            'service_fees' => ["numeric"],
        ];
    }

    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
            'message' => 'validation error',
            'errors' => $validator->errors()
        ],422));
    }
}
