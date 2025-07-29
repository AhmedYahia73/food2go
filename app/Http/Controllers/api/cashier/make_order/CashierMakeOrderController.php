<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Requests\cashier\DineinOrderRequest;
use App\Http\Requests\cashier\TakawayRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Carbon\Carbon;

// ____________________________________________________
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector; // Windows only
// ____________________________________________________

use App\Models\Order;
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

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;
use App\trait\POS;

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
    private Kitchen $kitchen, private KitchenOrder $kitchen_order){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    use POS;

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
        $connector = new WindowsPrintConnector("XPrinter"); 
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

    public function delivery_order(OrderRequest $request){
        // /cashier/delivery_order
        // Keys
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, customer_id
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
            'branch_id' => $request->user()->branch_id,
            'user_id' => 'empty',
            'order_type' => 'delivery',
            'cashier_man_id' =>$request->user()->id,
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
            'status' => 1
        ]); 
        $order = $this->take_away_make_order($request);

        return response()->json([
            'success' => $order['order'], 
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
        $order_cart = $this->order_cart
        ->where('table_id', $id)
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
        $order_cart = $this->order_cart
        ->where('table_id', $id)
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
            $this->kitchen_order
            ->create([
                'table_id' => $request->table_id,
                'kitchen_id' => $key,
                'order' => json_encode($item),
                'type' => 'dine_in',
            ]);
        }

        return response()->json([
            'success' => 'You perpare success'
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
        $order_carts = $this->order_cart
        ->where('table_id', $request->table_id)
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
        $order = $this->order_format(($order['payment']), $key);
        $this->cafe_table
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
        $this->cafe_table
        ->where('id', $id)
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
        $old_status = array_search($order->take_away_status, $status_arr);
        $new_status = array_search($request->take_away_status, $status_arr);
        if($new_status <= $old_status){
            return response()->json([
                'errors' => 'You can not back by status'
            ], 400);
        }
        if($request->take_away_status == 'preparing'){
            $this->preparing_takeaway($request, $id);
        }
        $order->take_away_status = $request->take_away_status;
        $order->save();

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    public function preparing_takeaway($request, $id){
        $order = $this->order
        ->where('id', $id)
        ->first(); 
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
                $kitchen_order[$kitchen->id][] = $element;
            }
        }
            
        foreach ($kitchen_order as $key => $item) {
            $this->kitchen_order
            ->create([
                'table_id' => $request->table_id,
                'kitchen_id' => $key,
                'order' => json_encode($item),
                'type' => $order->order_type,
                'order_id' => $order->id,
            ]);
        }
        return response()->json([
            'success' => $order_items
        ]);
    }
}
