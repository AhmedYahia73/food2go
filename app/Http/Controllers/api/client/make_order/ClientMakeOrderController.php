<?php

namespace App\Http\Controllers\api\client\make_order;

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
use App\Models\CashierBalance;
use App\Models\FinantiolAcounting;
use App\Models\Delivery;

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;
use App\trait\POS;

class ClientMakeOrderController extends Controller
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
    private CafeTable $cafe_tables, private FinantiolAcounting $finantiol_accounting){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    use POS;
 

    public function lists(Request $request){
        // /captain/lists
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $branch_id = $this->cafe_tables
        ->where('id', $request->table_id)
        ->with('location')
        ->first()
        ?->location?->branch_id;
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $option_off = $branch_off->pluck('option_id')->filter();

        $categories = $this->category
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->withLocale($locale)
        ->where('category_id', null)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        });
        $products = $this->products
        ->with(['addons' => function($query) use($locale){
            $query->withLocale($locale);
        },'sub_category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'excludes' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'extra', 'discount', 
        'variations' => function($query) use($locale){
            $query->withLocale($locale)
            ->with(['options' => function($query_option) use($locale){
                $query_option->with(['extra' => function($query_extra) use($locale){
                    $query_extra->with('parent_extra')
                    ->withLocale($locale);
                }])
                ->withLocale($locale);
            }]);
        }, 'sales_count', 'tax'])
        ->withLocale($locale)
        ->where('item_type', '!=', 'offline')
        ->where('status', 1)
        ->get()
        ->map(function($product) use($category_off, $product_off, $option_off, $branch_id){
            //get count of sales of product to detemine stock
            $product->price = $product?->product_pricing->where('branch_id', $branch_id)
            ->first()?->price ?? $product->price;
            $product->favourite = false;
            if ($product->stock_type == 'fixed') {
                $product->count = $product->sales_count->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            elseif ($product->stock_type == 'daily') {
                $product->count = $product->sales_count
                ->where('date', date('Y-m-d'))
                ->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            // return !$category_off->contains($item->id);
            // $category_off, $product_off, $option_off
            if ($category_off->contains($product->category_id) || 
            $category_off->contains($product->sub_category_id)
            || $product_off->contains($product->id)) {
                return null;
            }
            $product->variations = $product->variations->map(function ($variation) 
            use ($option_off, $product, $branch_id) {
                $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                $variation->options = $variation->options->map(function($element) use($branch_id){
                    $element->price = $element?->option_pricing->where('branch_id', $branch_id)
                    ->first()?->price ?? $element->price;
                    return $element;
                });
              
                return $variation;
            });
            $product->addons = $product->addons->map(function ($addon) 
            use ($product) {
                $addon->discount = $product->discount;
              
                return $addon;
            });
            return $product;
        })->filter(); 
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products); 
        $finantiol_accounting = $this->finantiol_accounting
        ->select('id', 'name', 'logo')
        ->where('status', 1)
        ->whereHas('branch', function($query) use($branch_id){
            $query->where('branches.id', $branch_id);
        })
        ->get();

        return response()->json([
            'categories' => $categories,
            'products' => $products,
            'finantiol_accounting' => $finantiol_accounting, 
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
        $branch_id = $this->cafe_tables
        ->where('id', $request->table_id)
        ->with('location')
        ->first()
        ?->location?->branch_id;
        $request->merge([
            'branch_id' => $branch_id,
            'user_id' => 'empty',
            'order_type' => 'dine_in', 
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
        $order = $this->order_format(($order['payment']), 0);
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

    public function dine_in_split_payment(DineinSplitRequest $request){
        // /cashier/delivery_order
        // Keys
        // amount, total_tax, total_discount, notes, address_id
        // source, financials[{id, amount}], cash_with_delivery
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
        $order_cart = $this->order_cart
        ->whereIn('id', $request->cart_id)
        ->delete();

        return response()->json([
            'success' => $order, 
        ]);
    }
}
