<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use App\trait\PaymobData;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\admin\settings\PaymentsMethodAutoRequest;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodAuto;

class PaymentMethodAutoController extends Controller
{
    public function __construct(private PaymentMethod $payment_methods, 
    private PaymentMethodAuto $payment_method_auto){}
    use image;
    use PaymobData;
    
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

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/settings/payment_methods_auto/status/{id}
        // Key
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $this->payment_methods
        ->where('id', $id)
        ->update(['status' => $request->status]);

        return response()->json([
            'success' => $request->status ? 'active' : 'banned'
        ]);
    }
    
    public function modify(PaymentsMethodAutoRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/payment_methods_auto/update/{id}
        // Keys
        // logo, title, type, callback, api_key, iframe_id, 
        // integration_id,  Hmac
        $paymentMethodRequest = $request->validated();
        $payment_method = $this->payment_methods
        ->where('id', $id)
        ->first();
        if (!is_string($request->logo)) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/payment_methods');
            $this->deleteImage($payment_method->logo);
            $payment_method->logo = $image_path;
            $payment_method->save();
        }
        $payment_method_auto = $this->payment_method_auto
        ->where('payment_method_id', $id)
        ->first();
        if (empty($payment_method_auto)) {
            $paymentMethodRequest['payment_method_id'] = $id;
            $this->payment_method_auto
            ->create($paymentMethodRequest);    
        } 
        else {
            $payment_method_auto->update($paymentMethodRequest);
        }
        // // Step 2: Get the singleton instance
        // $envManager = PaymobData::getInstance();

        // // Step 3: Set the new value dynamically
        // $envManager->setEnv('YOUR_ENV_KEY', $newValue);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
