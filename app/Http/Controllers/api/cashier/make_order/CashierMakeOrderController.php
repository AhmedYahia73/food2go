<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Requests\cashier\DineinSplitRequest;
use App\Http\Requests\cashier\DineinOrderRequest;
use App\Http\Requests\cashier\TakawayRequest;
use App\Http\Requests\cashier\DeliveryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Carbon\Carbon;

// ____________________________________________________
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector; // Windows only
// ____________________________________________________

use App\Events\PrintOrder;

use App\Models\Order;
use App\Models\UserDue;
use App\Models\Kitchen;
use App\Models\KitchenOrder;
use App\Models\OrderCart;
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
use App\Models\OrderFinancial;
use App\Models\CashierBalance;
use App\Models\CashierMan;
use App\Models\Delivery;
use App\Models\DiscountModule;
use App\Models\CheckoutRequest;// dicount_id
use App\Models\FinantiolAcounting;
use Illuminate\Support\Facades\Storage;

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;
use App\trait\POS;
use App\trait\Recipe;

class CashierMakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, private CafeTable $cafe_table,
    private CafeLocation $cafe_location, private OrderCart $order_cart,
    private TimeSittings $TimeSittings, private OrderFinancial $financial,
    private Kitchen $kitchen, private KitchenOrder $kitchen_order,
    private Delivery $delivery, private CashierBalance $cashier_balance,
    private CashierMan $cashier_man, private UserDue $user_due,
    private DiscountModule $discount_module, private FinantiolAcounting $financial_account,
    private CheckoutRequest $checkout_request_query){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    use POS;
    use Recipe; 

    public function list_due_users(Request $request){
        $users =$this->user
        ->where("due_status", 1)
        ->where("status", 1)
        ->get()
        ->select("phone", "name", "can_debit", "phone_2", "id");

        return response()->json([
            "users" => $users
        ]);
    }

    public function pos_orders(Request $request){
        // /cashier/orders
        
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
        ->where('cashier_man_id', $request->user()->id)
        ->orderByDesc('id')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 1)
        ->get();
        $delivery_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('cashier_man_id', $request->user()->id)
        ->where('order_type', 'delivery')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 1)
        ->get();
        $take_away_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('cashier_man_id', $request->user()->id)
        ->where('order_type', 'take_away')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 1)
        ->get();
        $dine_in_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('cashier_man_id', $request->user()->id)
        ->where('order_type', 'dine_in')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 1)
        ->get();
        $car_slow_order = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->orderByDesc('id')
        ->where('cashier_man_id', $request->user()->id)
        ->where('order_type', 'car_slow')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 1)
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
        ->where('cashier_man_id', $request->user()->id)
        ->where('order_type', 'delivery')
        ->whereNull('delivery_id')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 1)
        ->get();

        return response()->json([
            'all_orders' => $all_orders,
            'orders' => $orders,
            'orders_to_delivery' => $orders_to_delivery,
        ]);
    }

    public function printReceipt(Request $request)
    { 
        // cashier/printReceipt
        // $validator = Validator::make($request->all(), [
        //     'pdf' => 'required|file|mimes:pdf',
        // ]);
        // if ($validator->fails()) { // if Validate Make Error Return Message Error
        //     return response()->json([
        //         'errors' => $validator->errors(),
        //     ],400);
        // }
    
        // $connector = new WindowsPrintConnector("XPrinter");
        // $printer = new Printer($connector);
        // $pdf = $request->file('pdf');
        // $pdfPath = storage_path('app/public/temp_receipt.pdf');
        // $pdf->move(storage_path('app/public'), 'temp_receipt.pdf');
    
        // // Convert the PDF to image (use ImageMagick or imagick)
        // $imagick = new \Imagick();
        // $imagick->readImage($pdfPath);
        // $imagick->setImageFormat("png");
        // $imagePath = storage_path('app/public/temp_receipt.png');
        // $imagick->writeImage($imagePath);
    
        // // Print the image
        // $connector = new WindowsPrintConnector("XPrinter"); // Or CupsPrintConnector
        // $printer = new Printer($connector);
        // $printer->graphics(new \Mike42\Escpos\EscposImage($imagePath));
        // $printer->cut();
        // $printer->close();
    
        // return response()->json(['message' => 'Receipt printed successfully']);
                
    try {
        $connector = new WindowsPrintConnector("XP-370B"); 
        // $connector = new NetworkPrintConnector("192.168.1.15", 9100); 
        // Windows printer share name
        // OR use NetworkPrintConnector("192.168.0.100", 9100);

        $printer = new Printer($connector);
        $printer->pulse();  // This command sends a pulse to open the cash drawer
        // Print receipt content
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("My Store\n");
        $printer->text("123 Market Street\n");
        $printer->text("Tel: 0123456789\n");
        $printer->feed();

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Item        Qty    Price\n");
        $printer->text("--------------------------\n");
        $printer->text("Coffee       2     40.00\n");
        $printer->text("Donut        1     15.00\n");
        $printer->text("--------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text("TOTAL:       55.00\n");

        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Thank you!\n");
        $printer->cut();
        $printer->close();

        return response()->json(['message' => 'Printed successfully']);
    } catch (\Exception $e) {
        return response()->json(['errors' => 'Failed to print: ' . $e->getMessage()], 500);
    }
    }
    

    public function get_order($id){
        // /get_order/{id}
        $order = $this->order
        ->select('id', 'order_details')
        ->where('id', $id)
        ->first();
        $data = $this->order_format($order->order_details, 0);

        return response()->json([
            'order' => $data
        ]);
    }

    public function delivery_lists(Request $request){
        $delivery = $this->delivery
        ->where('status', 1)
        ->get()
        ?->select('id', 'f_name', 'l_name', 'phone', 'balance', 'image_link');
        $users = $this->user
        ->where('status', 1)
        ->get()
        ?->select('id', 'name', 'image_link', 'phone', 'phone_2');

        return response()->json([
            'deliveries' => $delivery,
            'users' => $users,
        ]);
    }

    public function delivery_order(DeliveryRequest $request){
        // /cashier/delivery_order
        // Keys
        // amount, total_tax, total_discount, notes, address_id
        // source, financials[{id, amount, description}], cash_with_delivery
        // cashier_id, user_id
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
        $request->merge([
            'branch_id' => $request->user()->branch_id, 
            'order_type' => 'delivery',
            'cashier_man_id' =>$request->user()->id,
            'shift' => $request->user()->shift_number,
            'pos' => 1,
            'cash_with_delivery' => $request->cash_with_delivery ?? false,
        ]); 
        if($request->dicount_id){
            if(!$request->user()->discount_perimission){
                return response()->json([
                    'errors' => "You don't have perimission to make discount"
                ], 400);
            }
            if (!$request->password || 
            !password_verify($request->input('password'), $request->user()->password)) {
                return response()->json([
                    'errors' => 'Password is wrong'
                ], 400);
            }
        }
        if($request->due){
            if(!$request->user_id){
                return response()->json([
                    "errors" => "user_id is  required"
                ], 400); 
            }
            $user = $this->user
            ->where("id", $request->user_id)
            ->first();
            $due = $user->due + $request->due;
            if($user->max_due < $due){
                return response()->json([
                    "errors" => "user is exceed the alloed limit"
                ], 400); 
            }
        }
        $kitchen_items = [];
        $order = $this->delivery_make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        if(!$request->order_pending){
            $kitchen_items = $this->preparing_delivery($request, $order['order']->id);
            $kitchen_items = $kitchen_items['kitchen_items'];
        }
        if($request->due){
            $user_due = $this->user_due
            ->create([
                "user_id" => $request->user_id,
                "order_id" => $order["order"]->id,
                "cashier_id" => $request->user()->id,
                "amount" => $request->amount,
            ]); 
            $user = $this->user
            ->where("id", $request->user_id)
            ->first();
            $user->update([
                "due" => $user->due + $request->amount
            ]);
        }      
        // Pull Pecipe
        $order_details = $order['order']->order_details;
        $products = [];
        foreach ($order_details as $item) {
            $product_item = $item->product[0]; 
            $products[] = [
                "id" => $product_item->product->id,
                "count" => $product_item->count,
            ]; 
        }
        $errors = $this->pull_recipe($products, $request->branch_id);
        if(!$errors['success']){
            return response()->json([
                "errors" => $errors['msg']
            ], 400);
        }
        event(new PrintOrder($order['order']));
        // _________________________________
        return response()->json([
            'success' => $order['order'],
            'kitchen_items' => $kitchen_items,
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

        $order = $this->order
        ->where('id', $order_id)
        ->first();

        // if cash with delivery 
        if($order->cash_with_delivery){
            $delivery = $this->delivery
            ->where('id', $order->delivery_id)
            ->first();
            $delivery->balance += $order->amount;
        }
        $order->update([
            'delivery_id' => $request->delivery_id
        ]);

        return response()->json([
            'success' => 'You select delivery success'
        ]);
    }

    public function delivery_cash(Request $request){
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
            'amount' => 'required|numeric',
            'cashier_id' => 'required|exists:cashiers,id'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $delivery = $this->delivery
        ->where('id', $request->delivery_id)
        ->where('balance', '>=', $request->amount)
        ->first();
        if(empty($delivery)){
            return response()->json([
                'errors' => 'Delivery Balance less than ' . $request->amount
            ], 400);
        }
        $cashier_balance = $this->cashier_balance
        ->where('cashier_man_id', $request->user()->id)
        ->where('cashier_id', $request->cashier_id)
        ->where('shift_number', $request->user()->shift_number)
        ->first();
        if(!empty($cashier_balance)){
            $cashier_balance->balance += $request->amount;
            $cashier_balance->save();
        }
        else{
            $this->cashier_balance
            ->create([
                'balance' => $request->amount, 
                'cashier_id' => $request->cashier_id,
                'cashier_man_id' => $request->user()->id,
            ]);
        }
        $delivery->balance -= $request->amount;
        $delivery->save();

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function order_status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'delivery_status' => 'required|in:preparing,done,ready_for_delivery,out_for_delivery,delivered,returned',
        ]); 
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $order = $this->order
        ->where('id', $id)
        ->where('pos', 1)
        ->first();
        if(empty($order)){
            return response()->json([
                'errors' => 'id is wrong'
            ], 400);
        } 
        if($order->delivery_status == 'returned'){
            $delivery = $this->delivery
            ->where('id', $order->delivery_id)
            ->first();
            if(!empty($delivery)){
                $delivery->balance -= $order->amount;
                $delivery->save();
            }
        }
        $order->delivery_status = $request->delivery_status;
        $order->save();

        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    public function take_away_order(TakawayRequest $request){
        // /cashier/take_away_order
        // Keys
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, order_type
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
        $request->merge([  
            'branch_id' => $request->user()->branch_id,
            'order_type' => 'take_away',
            'cashier_man_id' =>$request->user()->id,
            'shift' => $request->user()->shift_number,
            'pos' => 1,
            'status' => 1,
            'take_away_status' => 'preparing',
        ]);
        $errors = $this->finantion_validation($request);
        if(isset($errors['errors'])){
            return response()->json([
                'errors' => $errors,
            ], 400);
        }
        if($request->due && !$request->user_id){ 
            return response()->json([
                "errors" => "user_id is required"
            ], 400); 
        }
        if($request->dicount_id){
            if(!$request->user()->discount_perimission){
                return response()->json([
                    'errors' => "You don't have perimission to make discount"
                ], 400);
            }
            if (!$request->password || 
            !password_verify($request->input('password'), $request->user()->password)) {
                return response()->json([
                    'errors' => 'Password is wrong'
                ], 400);
            }
        }
        if($request->due){
            $user = $this->user
            ->where("id", $request->user_id)
            ->first();
            $due = $user->due + $request->due;
            if($user->max_due < $due){
                return response()->json([
                    "errors" => "user is exceed the alloed limit"
                ], 400); 
            }
        }
        $kitchen_items = [];
        if($request->order_pending){
            $order = $this->take_away_make_order($request);
        }
        else{
            $order = $this->take_away_make_order($request);
            if(!$request->order_pending){
                $kitchen_items = $this->preparing_takeaway($request, $order['order']->id);
                $kitchen_items = $kitchen_items['kitchen_items'];
            }
            if($request->due){
                $user_due = $this->user_due
                ->create([
                    "user_id" => $request->user_id,
                    "order_id" => $order["order"]->id,
                    "cashier_id" => $request->user()->id,
                    "amount" => $request->amount,
                ]); 
                $user = $this->user
                ->where("id", $request->user_id)
                ->first();
                $user->update([
                    "due" => $user->due + $request->amount
                ]);
            }
        }
      // Pull Pecipe
        $order_details = $order["order"]->order_details;
        $products = [];
        foreach ($order_details as $item) { 
            $product_item = $item->product[0]; 
            $products[] = [
                "id" => $product_item->product->id,
                "count" => $product_item->count,
            ];
        }
        $errors = $this->pull_recipe($products, $request->user()->branch_id); 
        if(!$errors['success']){
            return response()->json([
                "errors" => $errors['msg']
            ], 400);
        } 
        // _________________________________
        return response()->json([
            'success' => $order['order'],  
            "kitchen_items" => $kitchen_items,
        ]);
    }

    public function dine_in_order(OrderRequest $request){
        // /cashier/dine_in_order
        // Keys
        // amount, total_tax, total_discount, table_id
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
            'branch_id' => $request->user()->branch_id,
            'user_id' => 'empty',
            'order_type' => 'dine_in',
            'cashier_man_id' =>$request->user()->id,
            'shift' => $request->user()->shift_number,
        ]);
        $order = $this->make_order_cart($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->cafe_table
        ->where('id', $request->table_id)
        ->update([
            'current_status' => 'not_available_with_order'
        ]);
        $order_data = $this->order_format($order['payment'], 0);

        return response()->json([
            'success' => $order_data, 
        ]);
    }

    public function dine_in_table_carts(Request $request, $id){
        // /cashier/dine_in_table_carts/{id}
        $tables_ids = $this->cafe_table
        ->where('id', $id)
        ->orWhere('main_table_id', $id)
        ->pluck('id')
        ->toArray();
        $order_cart = $this->order_cart
        ->whereIn('table_id', $tables_ids)
        ->get();
        $carts = [];
        foreach ($order_cart as $key => $item) {
            $order_item = $this->order_format($item, $key);
            $carts[] = $order_item;
        }

        return response()->json([
            'carts' => $carts
        ]);
    }

    public function dine_in_table_order(Request $request, $id){
        // /cashier/dine_in_table_order/{id}
        $tables_ids = $this->cafe_table
        ->where('id', $id)
        ->orWhere('main_table_id', $id)
        ->pluck('id')
        ->toArray();
        $order_cart = $this->order_cart
        ->whereIn('table_id', $tables_ids)
        ->get();
        $orders = collect([]);
        foreach ($order_cart as $key => $item) {
            $order_item = $this->order_format($item, $key); 
            $orders = $orders->merge($order_item);
        }

        return response()->json([
            'success' => $orders
        ]);
    }

    public function preparing(Request $request){
        $validator = Validator::make($request->all(), [
            'preparing' => 'required',
            'preparing.*.cart_id' => 'required|exists:order_carts,id',
            'preparing.*.status' => 'required|in:preparing,done,pick_up',
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $kitchen_order = [];
        foreach ($request->preparing as $value) {
            $order_cart = $this->order_cart
            ->where('id', $value['cart_id'])
            ->first();
            $preparing = $order_cart->cart;
            $order_cart->prepration_status = $value['status'];  
            $order_cart->save();
            $order_item = $this->order_format($order_cart);
            $order_item = collect($order_item);

            $element = $order_item[0];
            $kitchen = $this->kitchen
            ->where(function($q) use($element){
                $q->whereHas('products', function($query) use ($element){
                    $query->where('products.id', $element->id);
                })
                ->orWhereHas('category', function($query) use ($element){
                    $query->where('categories.id', $element->category_id)
                    ->orWhere('categories.id', $element->sub_category_id);
                });
            })
            ->where('branch_id', $request->user()->branch_id)
            ->first();
            if(!empty($kitchen) && $value['status'] == 'preparing'){
                $kitchen_order[$kitchen->id][] = $element;
            }
        }
        foreach ($kitchen_order as $key => $item) {
            $kitchen_order = $this->kitchen_order
            ->create([
                'table_id' => $request->table_id,
                'kitchen_id' => $key,
                'order' => json_encode($item),
                'type' => 'dine_in',
                'cart_id' => $value['cart_id'],
            ]);
            $this->kitechen_cart($item, $kitchen_order );
        }

        return response()->json([
            'success' => 'You perpare success',
        ]);
    }

    public function dine_in_payment(DineinOrderRequest $request){
        // /cashier/dine_in_payment
        // Keys
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, table_id
  
        $request->merge([  
            'branch_id' => $request->user()->branch_id,
            'order_type' => 'dine_in',
            'cashier_man_id' =>$request->user()->id,
            'shift' => $request->user()->shift_number,
            'pos' => 1,
            'status' => 1,
        ]); 
        $errors = $this->finantion_validation($request);
        if(isset($errors['errors'])){
            return response()->json([
                'errors' => $errors,
            ], 400);
        }
        if($request->dicount_id){
            if(!$request->user()->discount_perimission){
                return response()->json([
                    'errors' => "You don't have perimission to make discount"
                ], 400);
            }
            if (!$request->password || 
            !password_verify($request->input('password'), $request->user()->password)) {
                return response()->json([
                    'errors' => 'Password is wrong'
                ], 400);
            }
        }
        $tables_ids = $this->cafe_table
        ->where('id', $request->table_id)
        ->orWhere('main_table_id', $request->table_id)
        ->pluck('id')
        ->toArray();
        $order_carts = $this->order_cart
        ->whereIn('table_id', $tables_ids)
        ->get();
        $orders = collect([]);
        $product = [];
        foreach ($order_carts as $key => $item) {
            $order_item = $this->order_format($item, $key);
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
        
        $order = $this->dine_in_make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        } 
        $order['payment']['cart'] = $order['payment']['order_details'];
        $order_items = $this->order_format(($order['payment']), 0);
        // Pull Pecipe
        $order_details = $order['payment']['order_details'];
        $products = [];
         
        foreach ($order_details as $item) { 
            $product_item = $item->product[0]; 
            $products[] = [
                "id" => $product_item->product->id,
                "count" => $product_item->count,
            ];
        }
        $errors = $this->pull_recipe($products, $request->user()->branch_id); 
        if(!$errors['success']){
            return response()->json([
                "errors" => $errors['msg']
            ], 400);
        } 
        // _________________________________
        $this->cafe_table
        ->whereIn('id', $tables_ids)
        ->update([
            'current_status' => 'not_available_but_checkout'
        ]);
        $order_cart = $this->order_cart
        ->whereIn('table_id', $tables_ids)
        ->delete();
        $this->checkout_request_query
        ->where("table_id", $request->table_id)
        ->delete();

        return response()->json([
            'success' => $order_items, 
        ]);
    }

    public function dine_in_split_payment(DineinSplitRequest $request){
        // /cashier/delivery_order
        // Keys
        // amount, total_tax, total_discount, notes, address_id
        // source, financials[{id, amount, description}], cash_with_delivery
        // cashier_id, user_id
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
        $request->merge([
            'branch_id' => $request->user()->branch_id, 
            'order_type' => 'dine_in',
            'cashier_man_id' =>$request->user()->id,
            'shift' => $request->user()->shift_number,
            'pos' => 1, 
        ]);
        $errors = $this->finantion_validation($request);
        if(isset($errors['errors'])){
            return response()->json([
                'errors' => $errors,
            ], 400);
        }
        if($request->dicount_id){
            if(!$request->user()->discount_perimission){
                return response()->json([
                    'errors' => "You don't have perimission to make discount"
                ], 400);
            }
            if (!$request->password || 
            !password_verify($request->input('password'), $request->user()->password)) {
                return response()->json([
                    'errors' => 'Password is wrong'
                ], 400);
            }
        }
        $order_carts = $this->order_cart
        ->whereIn('id', $request->cart_id)
        ->get();
        $orders = collect([]);
        $product = [];
        foreach ($order_carts as $key => $item) {
            $order_item = $this->order_format($item, $key);
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
        
        $order = $this->dine_in_make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        } 
        $order['payment']['cart'] = $order['payment']['order_details'];
        $order = $this->order_format(($order['payment']), 0);
      // Pull Pecipe
        $order_details = $order['payment']['order_details'];
        $products = [];
         
        foreach ($order_details as $item) { 
            $product_item = $item->product[0]; 
            $products[] = [
                "id" => $product_item->product->id,
                "count" => $product_item->count,
            ];
        }
        $errors = $this->pull_recipe($products, $request->user()->branch_id); 
        if(!$errors['success']){
            return response()->json([
                "errors" => $errors['msg']
            ], 400);
        } 
        // _________________________________
        $order_cart = $this->order_cart
        ->whereIn('id', $request->cart_id)
        ->delete();
        $this->checkout_request_query
        ->where("table_id", $request->table_id)
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
        $tables_ids = $this->cafe_table
        ->where('id', $id)
        ->orWhere('main_table_id', $id)
        ->pluck('id')
        ->toArray();
        $this->cafe_table
        ->whereIn('id', $tables_ids)
        ->update([
            'current_status' => $request->current_status
        ]);

        return response()->json([
            'success' => $request->current_status
        ]);
    }

    public function take_away_status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'take_away_status' => 'required|in:preparing,done,pick_up',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $status_arr = ['watting', 'preparing', 'done', 'pick_up'];
        $order = $this->order
        ->where('id', $id)
        ->first();
        $kitchen_items = [];
        $old_status = array_search($order->take_away_status, $status_arr);
        $new_status = array_search($request->take_away_status, $status_arr);
        if($new_status <= $old_status){
            return response()->json([
                'errors' => 'You can not back by status'
            ], 400);
        }
        if($request->take_away_status == 'preparing'){
            $order_details = $order->order_details;
            $products = [];
         
            foreach ($order_details as $item) { 
                $product_item = $item->product[0]; 
                $products[] = [
                    "id" => $product_item->product->id,
                    "count" => $product_item->count,
                ];
            }
            $errors = $this->pull_recipe($products, $request->user()->branch_id); 
            if(!$errors['success']){
                return response()->json([
                    "errors" => $errors['msg']
                ], 400);
        }    
            $kitchen_items = $this->preparing_takeaway($request, $id);
            $kitchen_items = $kitchen_items['kitchen_items'];
        }
        $order->take_away_status = $request->take_away_status;
        $order->save();

        return response()->json([
            'success' => 'You update status success',
            'kitchen_items' => $kitchen_items
        ]);
    } 

    public function preparing_delivery($request, $id){
        $order = $this->order
        ->where('id', $id)
        ->first(); 
        $kitchen_items = [];
        $order_items = $this->takeaway_order_format($order);
        $order_items = collect($order_items);
        $kitchen_order = [];
        foreach ($order_items as $key => $element) {
            $kitchen = $this->kitchen
            ->where(function($q) use($element){
                $q->whereHas('products', function($query) use ($element){
                    $query->where('products.id', $element->id);
                })
                ->orWhereHas('category', function($query) use ($element){
                    $query->where('categories.id', $element->category_id)
                    ->orWhere('categories.id', $element->sub_category_id);
                });
            })
            ->where('branch_id', $request->user()->branch_id)
            ->first();
            if(!empty($kitchen)){
                $kitchen_items[$kitchen->id] = $kitchen;
                $kitchen_order[$kitchen->id][] = $element;
            }
        }
            
        foreach ($kitchen_order as $key => $item) {
            $kitchen_items[$key] = [
                "id" => $kitchen_items[$key]->id,
                "name" => $kitchen_items[$key]->name,
                "print_name" => $kitchen_items[$key]->print_name,
                "print_ip" => $kitchen_items[$key]->print_ip,
                "print_status" => $kitchen_items[$key]->print_status,
                "order" => $item,
                "order_type" => $order->order_type,
            ];
            $order_kitchen =$this->kitchen_order
            ->create([
                'table_id' => $request->table_id,
                'kitchen_id' => $key,
                'order' => json_encode($item),
                'type' => $order->order_type,
                'order_id' => $order->id,
            ]); 
        } 
        $kitchen_items = array_values($kitchen_items);

        return [
            'success' => $order_items,
            'kitchen_items' => $kitchen_items,
        ];
    }

    public function preparing_takeaway($request, $id){
        $order = $this->order
        ->where('id', $id)
        ->first();  
        $order_items = $this->takeaway_order_format($order);
        $order_items = collect($order_items);
        $kitchen_order = [];
        $kitchen_items = [];
        $order_kitchen = [];
        foreach ($order_items as $key => $element) {
            $kitchen = $this->kitchen
            ->where(function($q) use($element){
                $q->whereHas('products', function($query) use ($element){
                    $query->where('products.id', $element->id);
                })
                ->orWhereHas('category', function($query) use ($element){
                    $query->where('categories.id', $element->category_id)
                    ->orWhere('categories.id', $element->sub_category_id);
                });
            })
            ->where('branch_id', $request->user()->branch_id)
            ->first();
            if(!empty($kitchen)){
                $kitchen_items[$kitchen->id] = $kitchen;
                $kitchen_order[$kitchen->id][] = $element;
            }
        }
            
        foreach ($kitchen_order as $key => $item) {
            $order_kitchen[$key] = [
                "id" => $kitchen_items[$key]->id,
                "name" => $kitchen_items[$key]->name,
                "print_name" => $kitchen_items[$key]->print_name,
                "print_ip" => $kitchen_items[$key]->print_ip,
                "print_status" => $kitchen_items[$key]->print_status,
                "order" => $item,
                "order_type" => $order->order_type,
            ];
            $kitchen_order = $this->kitchen_order
            ->create([
                'table_id' => $request->table_id,
                'kitchen_id' => $key,
                'order' => json_encode($item),
                'type' => $order->order_type,
                'order_id' => $order->id,
            ]);
            $this->kitechen_cart($item, $kitchen_order );
        }
        $order_kitchen = array_values($order_kitchen);

        return [
            'success' => $order_items,
            'kitchen_items' => $order_kitchen,
        ];
    }

    public function transfer_order(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'exists:order_carts,id',
            'table_id' => 'required|exists:cafe_tables,id'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $order_cart = $this->order_cart
        ->whereIn('id', $request->cart_ids)
        ->update([
            'table_id' => $request->table_id
        ]);
        $cafe_table = $this->cafe_table
        ->where('id', $request->table_id)
        ->update([
            'current_status' => 'not_available_with_order'
        ]);

        return response()->json([
            'success' => 'you transfer your table success',
            'status' => 'not_available_with_order',
        ]);
    } 

    public function order_void(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'exists:order_carts,id',
            'table_id' => 'required|exists:cafe_tables,id',
            'manager_id' => 'required',
            'manager_password' => 'required', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $cashier_man = $this->cashier_man
        ->where('my_id', $request->manager_id)
        ->first();
        if(empty($cashier_man) || !password_verify($request->input('manager_password'), $cashier_man->password)){
            return response()->json([
                'errors' => 'id or password is wrong'
            ], 400);
        }
        if(!$cashier_man->void_order){
            return response()->json([
                'errors' => "You don't have this premission"
            ], 400);
        }
        $order_cart = $this->order_cart
        ->whereIn('id', $request->cart_ids)
        ->delete();

        return response()->json([
            'success' => 'you void order success'
        ]);
    }

    public function discount_module(Request $request){
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $discount_module = $this->discount_module
        ->with('module')
        ->whereHas("module", function($query) use($request){
            $query->where("branch_id", $request->branch_id);
        })
        ->where("status", 1)
        ->first();

        return response()->json([
            "discount" => $discount_module->discount,
            "module" => $discount_module?->module?->pluck("module"),
        ]);
    }

    public function view_user_order(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $orders = $this->order
        ->where("user_id", $request->user_id)
        ->where("pos", 1)
        ->where("order_type", "delivery")
        ->orderByDesc("id")
        ->limit(3)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "total_discount" => $item->total_discount,
                "coupon_discount" => $item->coupon_discount,
                "order_number" => $item->order_number,
                "order_details" => $item->order_details,
                "date" => $item->created_at->format("Y-m-d"),
                "time" => $item->created_at->format("H:i:s"),
            ];
        });

        return response()->json([
            "orders" => $orders
        ]);
    }

    public function finantion_validation($request){
        $financial_ids = array_column($request->financials, 'id');
        $financial_account = $this->financial_account
        ->whereIn("id", $financial_ids)
        ->get();
        foreach ($financial_account as $item) { 
            $result = array_filter($request->financials, function($element) use($item) {
                return $element['id'] == $item->id;
            }); 
            $result = reset($result);
            if($item->description_status){
                if (!isset($result['description'])) {
                    return [
                        "errors" => 'Description is required at financial ' . $item->name
                    ];
                }
                if (!isset($result['transition_id'])) {
                    return [
                        "errors" => 'transition_id is required at financial ' . $item->name
                    ];
                }
            }
        }

        return ["success" => true];
    }
    

    public function certificate_sign(Request $request)
    {
        // 1. بناخد الداتا اللي جاية من الرياكت (من اللينك)
        $toSign = $request->request;

        if (!$toSign) {
            return response('No data to sign.', 400)->header('Content-Type', 'text/plain');
        }

        // 2. بنحدد مكان المفتاح السري بتاعنا
        $keyPath = '/private-key.pem'; // المسار اللي جوه storage/app/

        try {
            if (!Storage::exists($keyPath)) {
                // اتأكد إن الملف موجود
                return response('Private key not found.', 500)->header('Content-Type', 'text/plain');
            }

            // 3. بنقرأ محتويات المفتاح السري
            $privateKeyContents = Storage::get($keyPath);
            $privateKey = openssl_get_privatekey($privateKeyContents);

            if ($privateKey === false) {
                return response('Could not read private key.', 500)->header('Content-Type', 'text/plain');
            }

            $signature = null;

            // 4. بنعمل التوقيع! بنستخدم المفتاح السري عشان نوقّع على الداتا
            //    ده الـ algorithm. لازم نتأكد إنه SHA512
            openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);

            // 5. بنمسح المفتاح من الـ memory عشان الأمان
            openssl_free_key($privateKey);

            if ($signature) {
                // 6. بنرجع التوقيع (متشفّر) للرياكت
                //    مهم جدا نرجعه كـ text/plain مش JSON
                return response(base64_encode($signature), 200)
                          ->header('Content-Type', 'text/plain');
            }

            return response('Failed to generate signature.', 500)->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            // لو حصل أي ايرور، رجعه
            return response($e->getMessage(), 500)->header('Content-Type', 'text/plain');
        }
    }
}
