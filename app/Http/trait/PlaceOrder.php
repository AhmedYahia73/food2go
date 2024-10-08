<?php

namespace App\trait;

use App\Models\bundle;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use DragonCode\Contracts\Cashier\Config\Payments\Statuses;
use Error;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait PlaceOrder
{

     protected $orderPlaceReqeust =['chargeItems','amount','customerProfileId','payment_method_id','merchantRefNum'];
 // This Is Trait About Make any Order 
   

    public function placeOrder(Request $request ){
        
        $user = $request->user();
        $newOrder = $request->only($this->orderPlaceReqeust);
        $items = $newOrder['chargeItems'];
        // $user_id = $request->user()->id;
        $new_item = [];
        $service = $newOrder['chargeItems'][0]['description'];
        $amount = $newOrder['amount'];
         $paymentData = [
               "merchantRefNum"=> $newOrder['merchantRefNum'],
               "student_id"=> $newOrder['customerProfileId'],
               "amount"=> $newOrder['amount'],
               "service"=> $service,
               "purchase_date"=>now(),
        ];
         $paymentMethod = $this->paymenty_method->where('title','fawry')->first();
     
            if(empty($paymentMethod)){
                    return abort(404);
            }
                   $createPayment = $this->payment->create($paymentData);
        foreach ($items as $item) {
            $itemId = $item['itemId'];
            $item_type = $service == 'Bundle' ? 'bundle' : 'subject'; // iF Changed By Sevice Name Get Price One Of Them
            
            try {
             $payment_number = $createPayment->id;
            if($service == 'Bundle'){
                $newbundle = $createPayment->bundle()->sync($itemId);
              }elseif($service == 'Subject'){

                  $subject_id = $item['itemId'];
                  $bundleSubject = $user->bundles;
                  if(is_array($bundleSubject) && count($bundleSubject) > 0){
                            $studentSubject = $bundleSubject[0]->subjects->whereIn('id',$subject_id);
                            $studentSubjectID = $studentSubject->pluck('id')->toArray();
                            $subject_id = array_diff($subject_id,$studentSubjectID);
                  }
                $newSubjects = $createPayment->subject()->attach($subject_id);
              }
              } catch (\Throwable $th) {
               return abort(code: 500);
              }
            $data = [
                
                'paymentProcess' => $payment_number,
                    'chargeItems'=>[
                        'itemId'=>$itemId,
                        'description'=>$item_type,
                        'price'=>$amount,
                        'quantity'=>'1',
                    ]
            ];
              
            }
                  return $data ;
    }

    public function confirmOrder($response){
        if(isset($response['code']) && $response['code'] == 9901){
                return response()->json($response);
            }elseif(!isset($response['merchantRefNum'])){
                       $response =  response()->json(['faield'=>'Merchant Reference Number Not Found'],404);
                        return $response;
                    }else{
                  $merchantRefNum = $response['merchantRefNum'];
                  $customerMerchantId = $response['customerMerchantId'];
                  $orderStatus = $response['orderStatus'];
            }
  
            if($orderStatus == 'PAID'){
            $payment =
                $this->payment->where('merchantRefNum', $merchantRefNum)->with('bundle', function ($query):void {
                    $query->with('users');
                }, 'subject', function ($query):void {
                    $query->with('users');
           })->first();
            $order = $payment->service == 'Bundle' ? 'bundle' : 'subject';
            if($order == 'bundle'){
                $orderBundle = $payment->bundle;
                foreach($orderBundle as $student_bundle){
                     $student_bundle->users()->attach([$student_bundle->id=>['user_id'=>$customerMerchantId]] );
                }
            }elseif($order == 'subject'){
                $orderSubject= $payment->subject;
                 foreach($orderSubject as $student_subject){
                  $student_subject->users()->attach([$student_subject->id=>['user_id'=>$customerMerchantId]] );
                 }
            }

        }
        return response()->json($response);
    }
}
