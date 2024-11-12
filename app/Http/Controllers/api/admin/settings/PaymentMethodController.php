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
            $image_path = 
            $paymentMethodRequest['logo'] = $image_path;
        }
    }

    public function modify(PaymentMethodRequest $request, $id){
        
    }

    public function delete($id){
        
    }
}
