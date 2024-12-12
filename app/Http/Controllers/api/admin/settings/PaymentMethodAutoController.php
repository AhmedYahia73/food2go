<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use App\Http\Requests\admin\settings\PaymentsMethodAutoRequest;

use App\Models\PaymentMethod;

class PaymentMethodAutoController extends Controller
{
    public function __construct(private PaymentMethod $payment_methods){}
    use image;
    
    public function view(){
        // https://bcknd.food2go.online/admin/settings/payment_methods_auto
        $payment_methods = $this->payment_methods
        ->where('type', 'automatic')
        ->with('payment_method_data')
        ->get();

        return response()->json([
            'payment_methods' => $payment_methods
        ]);
    }
    
    public function modify(PaymentsMethodAutoRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/payment_methods/update/{id}
        // Keys
        // name, description, logo  
        $paymentMethodRequest = $request->validated();
        $payment_method = $this->payment_methods
        ->where('id', $id)
        ->first();
        if (!is_string($request->logo)) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/payment_methods');
            $paymentMethodRequest['logo'] = $image_path;
            $this->deleteImage($payment_method->logo);
        }
    }
}
