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
        // Keys
        // date, branch_id, amount, coupon_discount, total_tax, total_discount, address_id
        // order_type[take_away,dine_in,delivery], notes
        // deal[{deal_id, count}], payment_method_id, receipt
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
        $user = $request->user(); 
        $newOrder = $request->only($this->orderPlaceReqeust); 
        $items = $newOrder['chargeItems']; 
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


        $orderRequest = $request->only($this->orderRequest);
        $user = $request->user();
        $orderRequest['user_id'] = $user->id;
        $orderRequest['order_status'] = 'pending';
        $points = 0;
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'])
                ->first();
                if (!empty($item)) {
                    $points += $item->points * $product['count'];
                }
            }
        }
        if ($request->receipt) {
            $orderRequest['receipt'] = $request->receipt;
        }
        else {
        }
        $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
        $user->address()->attach($request->address_id);
        $user->save();
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $order_details[$key]['product'][] = [
                    'product' => $this->products
                    ->where('id', $product['product_id'])
                    ->first(),
                    'count' => $product['count']
                ];

                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'count' => $product['count'],
                    'product_index' => $key,
                ]); // Add product with count
                if (isset($product['exclude_id'])) {
                    foreach ($product['exclude_id'] as $exclude) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'exclude_id' => $exclude,
                            'count' => $product['count'],
                            'product_index' => $key,
                        ]); // Add excludes
                        
                        $order_details[$key]['excludes'][] = $this->excludes
                        ->where('id', $exclude)
                        ->first();
                    }
                }
                if (isset($product['addons'])) {
                    foreach ($product['addons'] as $addon) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'addon_id' => $addon['addon_id'],
                            'count' => $product['count'],
                            'addon_count' => $addon['count'],
                            'product_index' => $key,
                        ]); // Add excludes
                        
                        $order_details[$key]['addons'][] = [
                            'addon' => $this->addons
                            ->where('id', $addon['addon_id'])
                            ->first(),
                            'count' => $addon['count']
                        ];
                    }
                }
                if (isset($product['extra_id'])) {
                    foreach ($product['extra_id'] as $extra) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'extra_id' => $extra,
                            'count' => $product['count'],
                            'product_index' => $key,
                        ]); // Add extra
                        
                        $order_details[$key]['extras'][] = $this->extras
                        ->where('id', $extra)
                        ->first();
                    }
                }
                if (isset($product['variation'])) {
                    foreach ($product['variation'] as $variation) {
                        foreach ($variation['option_id'] as $option_id) {
                            $this->order_details
                            ->create([
                                'order_id' => $order->id,
                                'product_id' => $product['product_id'],
                                'variation_id' => $variation['variation_id'],
                                'option_id' => $option_id,
                                'count' => $product['count'],
                                'product_index' => $key,
                            ]); // Add variations & options
                        }
                        $order_details[$key]['variations'][] = [
                            'variation' => $this->variation
                            ->where('id', $variation['variation_id'])
                            ->first(),
                            'options' => $this->options
                            ->whereIn('id', $variation['option_id'])
                            ->get()
                        ];
                    }
                }
            }
        }
        $order->order_details = json_encode($order_details);
        $order->save();

        $data = [
            
            'paymentProcess' => $payment_number,
                'chargeItems'=>[
                    'itemId'=>$itemId,
                    'description'=>$item_type,
                    'price'=>$amount,
                    'quantity'=>'1',
                ]
        ];
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
            $orderRequest['status'] = 1;

            $user->points += $points;
            $user->save();

        }
        return response()->json($response);
    }
}
