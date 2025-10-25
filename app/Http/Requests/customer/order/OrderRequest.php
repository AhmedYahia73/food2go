<?php

namespace App\Http\Requests\customer\order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderRequest extends FormRequest
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
            'amount' => ['required', 'numeric'],
            'payment_method_id' => ['exists:payment_methods,id'],
            'total_tax' => ['required', 'numeric'],
            'total_discount' => ['required', 'numeric'],
            'address_id' => ['exists:addresses,id', 'nullable'],
            'order_type' => ['in:take_away,dine_in,delivery,car_slow'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['exists:products,id', 'required'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id'],
            'products.*.addons.*.count' => ['numeric'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required'],
            'products.*.note' => ['sometimes'],
            'sechedule_slot_id' => ['exists:schedule_slots,id'],
            'coupon_id' => ['exists:coupons,id']
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
