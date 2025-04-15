<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;

// ____________________________________________________
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
// ____________________________________________________

use App\Models\Order;
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

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;

class CashierMakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, private CafeTable $cafe_table,
    private CafeLocation $cafe_location, private OrderCart $order_cart){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;

    public function pos_orders(Request $request){
        // /cashier/orders
        $all_orders = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('cashier_man_id', $request->user()->id)
        ->orderByDesc('id')
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
        ->get();

        return response()->json([
            'all_orders' => $all_orders,
            'orders' => $orders,
            'orders_to_delivery' => $orders_to_delivery,
        ]);
    }

    public function printReceipt(Request $request)
    { 
        // $validator = Validator::make($request->all(), [
        //     'pdf' => 'required|file|mimes:pdf',
        // ]);
        // if ($validator->fails()) { // if Validate Make Error Return Message Error
        //     return response()->json([
        //         'error' => $validator->errors(),
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
            // Connect to your XPrinter (replace the name with actual printer name in Windows)
            $connector = new WindowsPrintConnector("XPrinter");

            $printer = new Printer($connector);
            $printer->text("Hello from Laravel!\n");
            $printer->feed(2);
            $printer->cut();
            $printer->close();

            return response()->json(['message' => 'Printed successfully!']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, order_type, customer_id
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $request->merge([  
            'branch_id' => $request->user()->branch_id,
            'user_id' => 'empty',
            'order_type' => 'delivery',
            'cashier_man_id' =>$request->user()->id,
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
        // /cashier/orders
        // Keys
        // delivery_id
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
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
            'branch_id' => $request->user()->branch_id,
            'user_id' => 'empty',
            'order_type' => 'take_away',
            'cashier_man_id' =>$request->user()->id,
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
        // notes, order_type
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
 
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $request->merge([  
            'branch_id' => $request->user()->branch_id,
            'user_id' => 'empty',
            'order_type' => 'delivery',
            'cashier_man_id' =>$request->user()->id,
        ]);
        $order = $this->make_order_cart($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->cafe_table
        ->where('id', $request->table_id)
        ->update([
            'occupied' => 1
        ]);
        $order_data = $this->order_format($order['payment']);

        return response()->json([
            'success' => $order_data, 
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
                'error' => $validator->errors(),
            ],400);
        }
        $request->merge([  
            'branch_id' => $request->user()->branch_id,
            'user_id' => 'empty',
            'order_type' => 'dine_in',
            'cashier_man_id' =>$request->user()->id,
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
            $product[$key]['variation'] = collect($item->variations)->map(function($element){
                return [
                    'variation_id' => $element->variation->id,
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
        ]);
        $order['payment']['cart'] = $order['payment']['order_details'];
        $order = $this->order_format(($order['payment']));
        $this->cafe_table
        ->where('id', $request->table_id)
        ->update([
            'occupied' => 0
        ]);
        $order_cart = $this->order_cart
        ->where('table_id', $request->table_id)
        ->delete();

        return response()->json([
            'success' => $order, 
        ]);
    }
}
