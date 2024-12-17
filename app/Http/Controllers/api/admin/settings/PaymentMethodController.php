<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\PaymentMethodRequest;
use App\trait\image;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

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
        // https://bcknd.food2go.online/admin/settings/payment_methods
        $payment_methods = $this->payment_methods
        ->where('type', 'manuel')
        ->get();

        return response()->json([
            'payment_methods' => $payment_methods
        ]);
    }

    public function payment_method($id){
        // https://bcknd.food2go.online/admin/settings/payment_methods/item/{id}
        $payment_method = $this->payment_methods
        ->where('type', 'manuel')
        ->where('id', $id)
        ->first();

        return response()->json([
            'payment_method' => $payment_method
        ]);
    }

    public function status(Request $request, $id){
          // https://bcknd.food2go.online/admin/settings/payment_methods/status/{id}
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->payment_methods->where('id', $id)
        ->update(['status' => $request->status]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    public function create(PaymentMethodRequest $request){
        // https://bcknd.food2go.online/admin/settings/payment_methods/add
        // Keys
        // name, description, status, logo
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
        // https://bcknd.food2go.online/admin/settings/payment_methods/update/{id}
        // Keys
        // name, description, status, logo
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
        // https://bcknd.food2go.online/admin/settings/payment_methods/delete/{id}
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
