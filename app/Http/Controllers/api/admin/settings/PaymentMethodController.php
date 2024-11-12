<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\PaymentMethodRequest;
use App\trait\image;

use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function __construct(private PaymentMethod $payment_methods){}
    protected $paymentMethodRequest = [
        'name',
        'description', 
        'status',
    ];
    use image;

    public function view(){
        $payment_methods = $this->payment_methods
        ->get();

        return response()->json([
            'payment_methods' => $payment_methods
        ]);
    }

    public function create(PaymentMethodRequest $request){
        $paymentMethodRequest = $request->only($this->paymentMethodRequest);
        if ($request->logo) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/payment_methods');
            $paymentMethodRequest['logo'] = $image_path;
        }
        $payment_method = $this->payment_methods
        ->create($paymentMethodRequest);

        return response()->json([
            'payment_method' => $payment_method
        ]);
    }

    public function modify(PaymentMethodRequest $request, $id){
        $paymentMethodRequest = $request->only($this->paymentMethodRequest);
        $payment_method = $this->payment_methods
        ->where('id', $id)
        ->first();
        if (!is_string($request->logo)) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/payment_methods');
            $paymentMethodRequest['logo'] = $image_path;
            $this->deleteImage($payment_method->logo);
        }
        $payment_method
        ->update($paymentMethodRequest);

        return response()->json([
            'payment_method' => $payment_method
        ]);
    }

    public function delete($id){
        $payment_method = $this->payment_methods
        ->where('id', $id)
        ->first();
        $this->deleteImage($payment_method->logo);
        $payment_method->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
