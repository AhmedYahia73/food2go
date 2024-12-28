<?php

namespace App\Http\Requests\admin\admin_roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminRoleRequest extends FormRequest
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
            'status' => ['required', 'boolean'],
            'roles' => ['array'],
            'roles.*' => ['in:Admin,Addons,AdminRoles,Banner,Branch,Category,Coupon,Customer,Deal,DealOrder,Delivery,OfferOrder,Order,Payments,PointOffers,Product,Settings,Home'],
        ];
    }

    public function failedValidation(Validator $validator){
       throw new HttpResponseException(response()->json([
               'message'=>'validation error',
               'errors'=>$validator->errors(),
       ],400));
   }
}
