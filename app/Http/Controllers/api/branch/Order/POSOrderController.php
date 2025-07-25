<?php

namespace App\Http\Controllers\api\branch\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSale;
use App\Models\Product;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\Addon;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\PaymentMethodAuto;
use App\Models\Category;
use App\Models\Setting;
use App\Models\BranchOff;
use App\Models\CafeLocation;
use App\Models\CafeTable;
use App\Models\TimeSittings;
use App\Models\OrderCart; 
use App\Models\Customer; 

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;

class POSOrderController extends Controller
{ 

    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, private CafeTable $tables,
    private CafeLocation $cafe_location, private TimeSittings $TimeSittings, 
    private OrderCart $order_cart, private PaymentMethod $payment_method,
    private Customer $customers){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;

    public function customer_data(Request $request){
        $customers = $this->customers
        ->with('addresses')
        ->get();

        return response()->json([
            'customers' => $customers
        ]);
    }

    public function pos_data(Request $request){
        $payment_method = $this->payment_method
        ->where('status', 1)
        ->get();
        $customers = $this->customers
        ->get();

        return response()->json([
            'payment_method' => $payment_method,
            'customers' => $customers,
        ]);
    }

    public function pos_orders(Request $request){
        // branch/pos_order
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                $start = $start->subDay();
			}
            // if ($start > $end) {
            //     $end = Carbon::parse($from)->addHours($hours)->subDay();
            // }
            // else{
            //     $end = Carbon::parse($from)->addHours(intval($hours));
            // }
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        }
        $all_orders = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('branch_id', $request->user()->id)
        ->orderByDesc('id')
        ->whereBetween('created_at', [$start, $end])
        ->get();
        $delivery_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('branch_id', $request->user()->id)
        ->where('order_type', 'delivery')
        ->whereBetween('created_at', [$start, $end])
        ->get();
        $take_away_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('branch_id', $request->user()->id)
        ->where('order_type', 'take_away')
        ->whereBetween('created_at', [$start, $end])
        ->get();
        $dine_in_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('branch_id', $request->user()->id)
        ->where('order_type', 'dine_in')
        ->whereBetween('created_at', [$start, $end])
        ->get();
        $car_slow_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('branch_id', $request->user()->id)
        ->where('order_type', 'car_slow')
        ->whereBetween('created_at', [$start, $end])
        ->get();
        $orders = [
            'delivery' => $delivery_order,
            'take_away' => $take_away_order,
            'dine_in' => $dine_in_order,
            'car_slow' => $car_slow_order,
        ];
        $orders_to_delivery = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('branch_id', $request->user()->id)
        ->where('order_type', 'delivery')
        ->whereNull('delivery_id')
        ->whereBetween('created_at', [$start, $end])
        ->get();

        return response()->json([
            'all_orders' => $all_orders,
            'orders' => $orders,
            'orders_to_delivery' => $orders_to_delivery,
        ]);
    }

    public function get_order($id){
        // /get_order/{id}
        $order = $this->order
        ->select('id', 'order_details')
        ->where('id', $id)
        ->first();
        $data = $this->order_format($order->order_details);

        return response()->json([
            'order' => $data
        ]);
    }

    public function delivery_order(OrderRequest $request){
        // /cashier/delivery_order
        // Keys
        // date, amount, total_tax, total_discount, address_id,
        // notes, payment_method_id, order_type, customer_id
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $request->merge([  
            'branch_id' => $request->user()->id,
            'user_id' => 'empty',
            'order_type' => 'delivery', 
            'shift' => $request->user()->shift_number,
        ]);
        $order = $this->make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->order
        ->where('id', $order['payment']->id)
        ->update([
            'pos' => 1
        ]);
        return response()->json([
            'success' => $order['payment'], 
        ]);
    }

    public function determine_delivery(Request $request, $order_id){
        // /cashier/determine_delivery/{order_id}
        // Keys
        // delivery_id
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->order
        ->where('id', $order_id)
        ->update([
            'delivery_id' => $request->delivery_id
        ]);

        return response()->json([
            'success' => 'You select delivery success'
        ]);
    }

    public function take_away_order(OrderRequest $request){
        // /cashier/take_away_order
        // Keys
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, order_type
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]

        $request->merge([  
            'branch_id' => $request->user()->id,
            'user_id' => 'empty',
            'order_type' => 'take_away',
            'shift' => $request->user()->shift_number,
        ]);
        $order = $this->make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->order
        ->where('id', $order['payment']->id)
        ->update([
            'pos' => 1,
            'status' => 1,
        ]);
        return response()->json([
            'success' => $order['payment'], 
        ]);
    }

    public function dine_in_order(OrderRequest $request){
        // /cashier/dine_in_order
        // Keys
        // date, amount, total_tax, total_discount, table_id
        // notes
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
 
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $request->merge([  
            'branch_id' => $request->user()->id,
            'user_id' => 'empty',
            'order_type' => 'dine_in',
            'shift' => $request->user()->shift_number,
        ]);
        $order = $this->make_order_cart($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->tables
        ->where('id', $request->table_id)
        ->update([
            'current_status' => 'not_available_with_order'
        ]);
        $order_data = $this->order_format($order['payment']);

        return response()->json([
            'success' => $order_data, 
        ]);
    }

    public function dine_in_table_carts(Request $request, $id){
        // /cashier/dine_in_table_carts/{id}
        $order_cart = $this->order_cart
        ->where('table_id', $id)
        ->get();
        $carts = [];
        foreach ($order_cart as $item) {
            $order_item = $this->order_format($item);
            $carts[] = $order_item;
        }

        return response()->json([
            'carts' => $carts
        ]);
    }

    public function dine_in_table_order(Request $request, $id){
        // /cashier/dine_in_table_order/{id}
        $order_cart = $this->order_cart
        ->where('table_id', $id)
        ->get();
        $orders = collect([]);
        foreach ($order_cart as $item) {
            $order_item = $this->order_format($item);
            $orders = $orders->merge($order_item);
        }

        return response()->json([
            'success' => $orders
        ]);
    }

    public function dine_in_payment(OrderRequest $request){
        // /cashier/dine_in_payment
        // Keys
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, table_id

        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $request->merge([  
            'branch_id' => $request->user()->id,
            'user_id' => 'empty',
            'order_type' => 'dine_in',
        ]);
        $order_carts = $this->order_cart
        ->where('table_id', $request->table_id)
        ->get();
        $orders = collect([]);
        $product = [];
        foreach ($order_carts as $item) {
            $order_item = $this->order_format($item);
            $orders = $orders->merge($order_item);
        }
       
        foreach ($orders as $key => $item) {
            $product[$key]['exclude_id'] = collect($item->excludes)->pluck('id');
            $product[$key]['extra_id'] = collect($item->extras)->pluck('id');
            $product[$key]['variation'] = collect($item->variation_selected)->map(function($element){
                return [
                    'variation_id' => $element->id,
                    'option_id' => collect($element->options)->pluck('id'),
                ];
            });
            $product[$key]['addons'] = collect($item->addons_selected)->map(function($element){
                return [
                    'addon_id' => ($element->id),
                    'count' => ($element->count),
                ];
            }); 
        
            $product[$key]['count'] = $item->count;
            $product[$key]['product_id'] = $item->id;
        }
        $request->merge([  
            'products' => $product, 
        ]);
        
        $order = $this->make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->order
        ->where('id', $order['payment']->id)
        ->update([
            'pos' => 1,
            'status' => 1,
            'shift' => $request->user()->shift_number,
        ]);
        $order['payment']['cart'] = $order['payment']['order_details'];
        $order = $this->order_format(($order['payment']));
        $this->tables
        ->where('id', $request->table_id)
        ->update([
            'current_status' => 'not_available_but_checkout'
        ]);
        $order_cart = $this->order_cart
        ->where('table_id', $request->table_id)
        ->delete();

        return response()->json([
            'success' => $order, 
        ]);
    }

    public function tables_status(Request $request, $id){
        // /cashier/tables_status/{id}
        // Keys
        // current_status => [available,not_available_pre_order,not_available_with_order,not_available_but_checkout,reserved]
        $validator = Validator::make($request->all(), [
            'current_status' => 'required|in:available,not_available_pre_order,not_available_with_order,not_available_but_checkout,reserved',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $this->tables
        ->where('id', $id)
        ->update([
            'current_status' => $request->current_status
        ]);

        return response()->json([
            'success' => $request->current_status
        ]);
    }
    // 
}
