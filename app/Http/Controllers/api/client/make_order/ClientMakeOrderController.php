<?php

namespace App\Http\Controllers\api\client\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Requests\cashier\DineinSplitRequest;
use App\Http\Requests\cashier\DineinOrderRequest;
use App\Http\Requests\cashier\TakawayRequest;
use App\Http\Requests\client\DineinClientOrderRequest;
use App\Http\Requests\cashier\DeliveryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Events\OrderEvent;
use Carbon\Carbon;

// ____________________________________________________
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector; // Windows only
// ____________________________________________________

use App\trait\Notifications; 

use App\Models\NewNotification; 
use App\Models\DeviceToken;
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
use App\Models\CashierMan;
use App\Models\Branch;
use App\Models\CaptainOrder;

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
    private CafeTable $cafe_tables, private FinantiolAcounting $finantiol_accounting,
    private Category $categories, private Product $product,
    private DeviceToken $device_token, private CashierMan $cashier_man,
    private CaptainOrder $captain_order){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    use POS; 
    use Notifications;


    public function discount_product(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_id = 0;
        $module = "delivery";
        $branch_id = $this->cafe_tables
        ->where("id", $request->table_id)
        ->first()
        ?->location?->branch_id ?? 0;
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter(); 
        $option_off = $branch_off->pluck('option_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $tax = $this->settings
        ->where('name', 'tax')
        ->orderByDesc('id')
        ->first();
        if (!empty($tax)) {
            $tax = $tax->setting;
        }
        else {
            $tax = $this->settings
            ->create([
                'name' => 'tax',
                'setting' => 'included',
            ]);
            $tax = $tax->setting;
        }
        $products = $this->product 
        ->orderBy('order')
        ->withLocale($locale)
        ->where('item_type', '!=', 'online')
        ->whereHas('discount')
        ->where('status', 1) 
        // ->whereNotIn('sub_category_id', $category_off)
        ->where(function($query) use($category_off){
            $query->whereNotIn('sub_category_id', $category_off)
            ->orWhereNotIn('category_id', $category_off);
        })
        ->whereNotIn('products.id', $product_off)
        ->get()
        ->map(function ($product) use ($option_off, $branch_id, $module) {
            $product->favourite = $product->favourite_product->isNotEmpty();

            $tax_module = $product?->tax
            ?->tax_module
            ?->map(function ($taxItem) use ($module, $branch_id, $product) {

                $isFound = $taxItem->module
                ->where('module', $module) 
                ->whereIn('app_type', ['online', 'all'])
                ->Where("branch_id", $branch_id)
                ->first();
                if($isFound){
                    return $product?->tax;
                }

            })
            ->filter()
            ->first();
            if(!empty($tax_module)){  
                $product->tax = $tax_module;
            }
            else{
                $product->tax = null;
            }
            if ($product->taxes->setting == 'included') {
                $price = empty($product->tax) ? $product->price: 
                ($product->tax->type == 'value' ? $product->price + $product->tax->amount 
                : $product->price + $product->tax->amount * $product->price / 100);
                
                if (!empty($product->discount)) {
                    if ($product->discount->type == 'precentage') {
                        $discount = $price - $product->discount->amount * $price / 100;
                        $discount_val = $product->discount->amount * $price / 100;
                    } else {
                        $discount = $price - $product->discount->amount;
                        $discount_val = $product->discount->amount;
                    }
                }
                else{
                    $discount = $price;
                    $discount_val = 0;
                }
                $tax = $price;
                return [
                    'id' => $product->id,
                    'taxes' => $product->taxes->setting,
                    'name' => $product->translations->where('key', $product->name)->first()?->value ?? $product->name,
                    'description' => $product->translations->where('key', $product->description)->first()?->value ?? $product->description,
                    'price' => $price,
                    'price_after_discount' => $discount,
                    'price_after_tax' => $tax, 
                    'image_link' => $product->image_link,
                    'discount' => $price - $discount,
                    'tax' => $tax - $price,
                ];
            } 
            else {
                $price = $product->price;
                
                if (!empty($product->tax)) {
                    if ($product->tax->type == 'precentage') {
                        $tax = $price + $product->tax->amount * $price / 100;
                    } else {
                        $tax = $price + $product->tax->amount;
                    }
                }
                else{
                    $tax = $price;
                }

                if (!empty($product->discount)) {
                    if ($product->discount->type == 'precentage') {
                        $discount = $price - $product->discount->amount * $price / 100;
                    } else {
                        $discount = $price - $product->discount->amount;
                    }
                }
                else{
                    $discount = $price;
                }
                return [
                    'id' => $product->id,
                    'taxes' => $product->taxes->setting,
                    'name' => $product->translations->where('key', $product->name)->first()?->value ?? $product->name,
                    'description' => $product->translations->where('key', $product->description)->first()?->value ?? $product->description,
                    'price' => $price,
                    'price_after_discount' => $discount,
                    'price_after_tax' => $tax,
                    'image_link' => $product->image_link,
                    'discount' => $price - $discount,
                    'tax' => $tax - $price,
                ];
            }
        });

        return response()->json([
            'discount_products' => $products
        ]);
    } 

    public function favourit_product(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_id = 0;
        $module = "delivery";
        $branch_id = $this->cafe_tables
        ->where("id", $request->table_id)
        ->first()
        ?->location?->branch_id ?? 0;
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter(); 
        $option_off = $branch_off->pluck('option_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $tax = $this->settings
        ->where('name', 'tax')
        ->orderByDesc('id')
        ->first();
        if (!empty($tax)) {
            $tax = $tax->setting;
        }
        else {
            $tax = $this->settings
            ->create([
                'name' => 'tax',
                'setting' => 'included',
            ]);
            $tax = $tax->setting;
        }
        $products = $this->product 
        ->orderBy('order')
        ->withLocale($locale)
        ->where('item_type', '!=', 'online')
        ->where('favourite', 1)
        ->where('status', 1) 
        // ->whereNotIn('sub_category_id', $category_off)
        ->where(function($query) use($category_off){
            $query->whereNotIn('sub_category_id', $category_off)
            ->orWhereNotIn('category_id', $category_off);
        })
        ->whereNotIn('products.id', $product_off)
        ->get()
        ->map(function ($product) use ($option_off, $branch_id, $module) {
            $product->favourite = $product->favourite_product->isNotEmpty();

            $tax_module = $product?->tax
            ?->tax_module
            ?->map(function ($taxItem) use ($module, $branch_id, $product) {

                $isFound = $taxItem->module
                ->where('module', $module) 
                ->whereIn('app_type', ['online', 'all'])
                ->Where("branch_id", $branch_id)
                ->first();
                if($isFound){
                    return $product?->tax;
                }

            })
            ->filter()
            ->first();
            if(!empty($tax_module)){  
                $product->tax = $tax_module;
            }
            else{
                $product->tax = null;
            }
            if ($product->taxes->setting == 'included') {
                $price = empty($product->tax) ? $product->price: 
                ($product->tax->type == 'value' ? $product->price + $product->tax->amount 
                : $product->price + $product->tax->amount * $product->price / 100);
                
                if (!empty($product->discount)) {
                    if ($product->discount->type == 'precentage') {
                        $discount = $price - $product->discount->amount * $price / 100;
                        $discount_val = $product->discount->amount * $price / 100;
                    } else {
                        $discount = $price - $product->discount->amount;
                        $discount_val = $product->discount->amount;
                    }
                }
                else{
                    $discount = $price;
                    $discount_val = 0;
                }
                $tax = $price;
                return [
                    'id' => $product->id,
                    'taxes' => $product->taxes->setting,
                    'name' => $product->translations->where('key', $product->name)->first()?->value ?? $product->name,
                    'description' => $product->translations->where('key', $product->description)->first()?->value ?? $product->description,
                    'price' => $price,
                    'price_after_discount' => $discount,
                    'price_after_tax' => $tax, 
                    'image_link' => $product->image_link,
                    'discount' => $price - $discount,
                    'tax' => $tax - $price,
                ];
            } 
            else {
                $price = $product->price;
                
                if (!empty($product->tax)) {
                    if ($product->tax->type == 'precentage') {
                        $tax = $price + $product->tax->amount * $price / 100;
                    } else {
                        $tax = $price + $product->tax->amount;
                    }
                }
                else{
                    $tax = $price;
                }

                if (!empty($product->discount)) {
                    if ($product->discount->type == 'precentage') {
                        $discount = $price - $product->discount->amount * $price / 100;
                    } else {
                        $discount = $price - $product->discount->amount;
                    }
                }
                else{
                    $discount = $price;
                }
                return [
                    'id' => $product->id,
                    'taxes' => $product->taxes->setting,
                    'name' => $product->translations->where('key', $product->name)->first()?->value ?? $product->name,
                    'description' => $product->translations->where('key', $product->description)->first()?->value ?? $product->description,
                    'price' => $price,
                    'price_after_discount' => $discount,
                    'price_after_tax' => $tax,
                    'image_link' => $product->image_link,
                    'discount' => $price - $discount,
                    'tax' => $tax - $price,
                ];
            }
        });

        return response()->json([
            'products' => $products
        ]);
    }

    public function category(Request $request){
        // https://bcknd.food2go.online/client/order/products/{id}
        // Keys
        // address_id, branch_id
        
        // // _______________________________________________________________________
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
        ?->location?->branch_id ?? 0;

        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get(); 
        $category_off = $branch_off->pluck('category_id')->filter(); 

        $categories = $this->categories
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->orderBy('priority')
        ->withLocale($locale)
        ->where('category_id', null)
        ->where('status', 1)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        });  
        $resturant_time = $this->settings
        ->where('name', 'resturant_time')
        ->orderByDesc('id')
        ->first();   
        $categories = CategoryResource::collection($categories); 

        return response()->json([
            'categories' => $categories, 
            'resturant_time' => $resturant_time, 
        ]);
    }

    public function products(Request $request, $id){
        // https://bcknd.food2go.online/client/order/products/{id}
        // Keys
        // address_id, branch_id
        
        // // _______________________________________________________________________
        $branch_id = $this->cafe_tables
        ->where('id', $id)
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

        $categories = $this->categories
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->orderBy('priority')
        ->withLocale($locale)
        ->where('category_id', null)
        ->where('status', 1)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        }); 
        $discounts = $this->product
        ->orderBy('order')
        ->with('discount')
        ->whereHas('discount')
        ->get();
        $resturant_time = $this->settings
        ->where('name', 'resturant_time')
        ->orderByDesc('id')
        ->first();
        if (!empty($resturant_time)) {
            $resturant_time = $resturant_time->setting;
            $resturant_time = json_decode($resturant_time) ?? $resturant_time;
        }
        $tax = $this->settings
        ->where('name', 'tax')
        ->orderByDesc('id')
        ->first();
        if (!empty($tax)) {
            $tax = $tax->setting;
        }
        else {
            $tax = $this->settings
            ->create([
                'name' => 'tax',
                'setting' => 'included',
            ]);
            $tax = $tax->setting;
        }
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products);

        return response()->json([
            'categories' => $categories,
            'discounts' => $discounts,
            'resturant_time' => $resturant_time,
            'tax' => $tax,
            'branch_id' => $branch_id
        ]);
    }

    public function products_in_category(Request $request, $id){ 
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $branch_id = $this->cafe_tables
        ->where("id", $request->table_id)
        ->first()
        ?->location?->branch_id;
        $locale = $request->locale ?? "en";
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter(); 
        $option_off = $branch_off->pluck('option_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $products = $this->product
        ->orderBy('order')
        ->with([
            'addons' => fn($q) => $q->withLocale($locale),
            'category_addons' => fn($q) => $q->withLocale($locale),
            'sub_category_addons' => fn($q) => $q->withLocale($locale),
            'excludes' => fn($q) => $q->withLocale($locale),
            'discount', 'extra', 'sales_count', 'tax',
            'variations' => fn($q) => $q->with([
                'options' => fn($oq) => $oq->with(['option_pricing']) // تأكد دي مطلوبة
            ])->withLocale($locale),
        
            'group_products' => fn($q) => $q
            ->with(['products' => fn($q) => $q
            ->select("products.id", "products.name")->withLocale($locale)]),
        ])
        ->withLocale($locale)
        ->where('item_type', '!=', 'offline')
        ->where('status', 1)
        ->whereNotIn('category_id', $category_off)
        //->whereNotIn('sub_category_id', $category_off)
        ->whereNotIn('products.id', $product_off)
        ->get();

        $products = $products->map(function($product) use ($branch_id, $option_off) {
            $product->price = $product->product_pricing
                ->firstWhere('branch_id', $branch_id)?->price ?? $product->price;

            $product->variations = $product->variations->map(function($variation) use ($option_off, $branch_id) {
                $variation->options = $variation->options
                    ->where('status', 1)
                    ->values()
                    ->reject(fn($opt) => $option_off->contains($opt->id))
                    ->map(function($opt) use ($branch_id) {
                        $opt->price = $opt->option_pricing
                            ->firstWhere('branch_id', $branch_id)?->price ?? $opt->price;
                        return $opt;
                    });

                return $variation;
            });
            return $product;
        });

        return response()->json([
            "products" => $products
        ]);
    }

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
        $branch_name = Branch::
        where("id", $branch_id)
        ->first()?->name;
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
        ->orderBy('order')
        ->with(['addons' => function($query) use($locale){
            $query->withLocale($locale);
        },'sub_category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'excludes' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'extra', 'discount', 
        'group_products' => fn($q) => $q
        ->with(['products' => fn($q) => $q
        ->select("products.id", "products.name")->withLocale($locale)]),
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
        $paymentMethod = $this->paymentMethod
        ->where('id', 1)
        ->get();
        $financial_account = $this->finantiol_accounting
        ->select('id', 'name', 'details', 'logo', 'description_status', 'discount')
        ->whereHas('branch')
        ->where('status', 1)
        ->get(); 

        return response()->json([
            'categories' => $categories,
            'products' => $products,
            'paymentMethod' => $paymentMethod,
            'financial_account' => $financial_account,
            "branch_id" => $branch_id,
            "branch_name" => $branch_name,
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

    public function dine_in_order(Request $request){
        // /cashier/dine_in_order
        // Keys
        // amount, total_tax, total_discount, table_id
        // notes
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
 
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
            'lat' => 'required',
            'lng' => 'required',
            'date' => ['regex:/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/'],
            'branch_id' => ['exists:branches,id', 'nullable'],
            'amount' => ['numeric'],
            'payment_method_id' => ['exists:payment_methods,id'],
            'total_tax' => ['numeric'],
            'total_discount' => ['numeric'],
            'address_id' => ['exists:addresses,id', 'nullable'],
            'order_type' => ['in:take_away,dine_in,delivery,car_slow'],
            'products' => ['required'],
            'products.*.product_id' => ['exists:products,id', 'required'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required'],
            'products.*.note' => ['sometimes'],
            'sechedule_slot_id' => ['exists:schedule_slots,id'],
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
        $branch_name = Branch::
        where("id", $branch_id ?? $request->branch_id)
        ->first()?->name;
        $location = $this->cafe_tables
        ->where('id', $request->table_id)
        ->with('location')
        ->first()
        ?->location;
        $polygon = $location?->location ?? [];

        $userLocation = ['lat' => $request->lat, 'lng' => $request->lng];
        if (!$this->isPointInPolygon($userLocation, $polygon)) {
            return response()->json([
                'errors' => 'You must be at location'
            ], 400);
        } 
        $branch_id = $location?->branch_id;
        $request->merge([
            'branch_id' => $branch_id,
            'user_id' => 'empty',
            'order_type' => 'dine_in', 
        ]);
        $order = $this->make_order_cart($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $cafe_table = $this->cafe_table
        ->where('id', $request->table_id)
        ->first();
        $cafe_table->update([
            'current_status' => 'not_available_with_order'
        ]);
        $device_token1 = $this->cashier_man
        ->where("branch_id", $cafe_table->branch_id)
        ->pluck("fcm_token")
        ->filter();
        $device_token2 = $this->captain_order
        ->where("branch_id", $cafe_table->branch_id)
        ->pluck("fcm_token")
        ->filter();
        $device_token3 = $this->device_token
        ->where("branch_id", $cafe_table->branch_id)
        ->pluck('token');
        $device_token = $device_token1->merge($device_token2)->merge($device_token3);
        $device_token = $device_token->filter()->toArray();
        $body = 'Table ' . $cafe_table->table_number . 
        ' at location ' . $cafe_table?->location?->name . ' Make Order';
        $this->sendNotificationToMany($device_token, $cafe_table->table_number, $body);
        $order_data = $this->order_format($order['payment'], 0);

        return response()->json([
            'success' => $order_data, 
            "branch_name" => $branch_name,
            "branch_id" => $branch_id,
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
        $carts = collect($carts)->flatten(1);

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
 
    public function dine_in_payment(DineinClientOrderRequest $request){
        // /cashier/dine_in_payment
        // Keys
        // amount, total_tax, total_discount
        // notes, payment_method_id, table_id
        $branch_id = $this->cafe_tables
        ->where('id', $request->table_id)
        ->with('location')
        ->first()
        ?->location?->branch_id; 
        $request->merge([  
            'branch_id' => $branch_id,
            'order_type' => 'dine_in',
            'pos' => 1,
            'status' => 1,
			'user_id' => 'empty',
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
        // ____________________________________________
        if ($request->payment_method_id == 1) {
            $payment_method_auto = $this->payment_method_auto
            ->where('payment_method_id', 1)
            ->first();
            $tokens = $this->getToken($payment_method_auto);
            $user = $request->user();
            $amount_cents = $request->amount * 100;
            $order = $this->createOrder($request, $tokens, $user);
            if (is_array($order) && isset($order['errors']) && !empty($order['errors'])) {
                return response()->json($order, 400);
            }
            $order_id = $this->order
            ->where('transaction_id', $order->id)
            ->first();
            $order_id->from_table_order = 1;
            $order_id->save();
            broadcast(new OrderEvent($order_id))->toOthers();
            // $order = $this->make_order($request);
            // $order = $order['payment']; 
            $paymentToken = $this->getPaymentToken($user, $amount_cents, $order, $tokens, $payment_method_auto);
             $paymentLink = "https://accept.paymob.com/api/acceptance/iframes/" . $payment_method_auto->iframe_id . '?payment_token=' . $paymentToken;
            $this->cafe_table
            ->where('id', $request->table_id)
            ->update([
                'current_status' => 'not_available_but_checkout'
            ]);
            $order_cart = $this->order_cart
            ->where('table_id', $request->table_id)
            ->delete();
            return response()->json([
                'success' => $order_id->id,
                'paymentLink' => $paymentLink,
            ]);
        }  

        return response()->json([
            'errors' => 'You must select visa', 
        ], 400);
    }

    public function dine_in_split_payment(DineinSplitRequest $request){
        // /cashier/delivery_order
        // Keys
        // amount, total_tax, total_discount, notes, address_id
        // source, payment_method_id, cash_with_delivery
        // cashier_id, user_id
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
        $request->merge([ 
            'order_type' => 'dine_in', 
            'pos' => 1, 
			'user_id' => 'empty',
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
        // ____________________________________________
        if ($request->payment_method_id == 1) {
            $payment_method_auto = $this->payment_method_auto
            ->where('payment_method_id', 1)
            ->first();
            $tokens = $this->getToken($payment_method_auto);
            $user = $request->user();
            $amount_cents = $request->amount * 100;
            $order = $this->createOrder($request, $tokens, $user);
            if (is_array($order) && isset($order['errors']) && !empty($order['errors'])) {
                return response()->json($order, 400);
            }
            $order_id = $this->order
            ->where('transaction_id', $order->id)
            ->first();
            $order_id->from_table_order = 1;
            $order_id->save();
            broadcast(new OrderEvent($order_id))->toOthers();
            // $order = $this->make_order($request);
            // $order = $order['payment']; 
            $paymentToken = $this->getPaymentToken($user, $amount_cents, $order, $tokens, $payment_method_auto);
             $paymentLink = "https://accept.paymob.com/api/acceptance/iframes/" . $payment_method_auto->iframe_id . '?payment_token=' . $paymentToken;
            $this->cafe_table
            ->where('id', $request->table_id)
            ->update([
                'current_status' => 'not_available_but_checkout'
            ]);
            $order_cart = $this->order_cart
            ->whereIn('id', $request->cart_id)
            ->delete();
            return response()->json([
                'success' => $order_id->id,
                'paymentLink' => $paymentLink,
            ]);
        }  

        return response()->json([
            'errors' => 'You must select visa', 
        ], 400);
    }

    function isPointInPolygon($point, $polygon) {
        $x = $point['lat'];
        $y = $point['lng'];
        $inside = false;

        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat']; // lat
            $yi = $polygon[$i]['lng']; // lng
            $xj = $polygon[$j]['lat']; 
            $yj = $polygon[$j]['lng'];

            $intersect = (($yi > $y) != ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) $inside = !$inside;
        }

        return $inside;
    }

    public function callback(Request $request){
        // https://bcknd.food2go.online/customer/callback
        $payment_method_auto = $this->payment_method_auto
        ->where('payment_method_id', 1)
        ->first();
        
        //this call back function its return the data from paymob and we show the full response and we checked if hmac is correct means successfull payment
        $data = $request->all();
        ksort($data);
        $hmac = $data['hmac'];
        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];
        $connectedString = '';
        foreach ($data as $key => $element) {
            if (in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = $payment_method_auto->Hmac;
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac) {
            //this below data used to get the last order created by the customer and check if its exists to 
            // $todayDate = Carbon::now();
            // $datas = Order::where('user_id',Auth::user()->id)->whereDate('created_at',$todayDate)->orderBy('created_at','desc')->first();
            $status = $data['success']; 
            // $pending = $data['pending'];
            if ($status == "true") { 
                //here we checked that the success payment is true and we updated the data base and empty the cart and redirct the customer to thankyou page
                $order = $this->order
                ->where('transaction_id', $data['order'])
                ->first();
                $order->update([
                    'status' => 1,
                    'order_status' => 'processing'
                ]);
                $user = $this->user
                ->where('id', $order->user_id)
                ->first();
                $user->points += $order->points;
                $user->save();
                $totalAmount = $data['amount_cents'];
                $message = 'Your payment is being processed. Please wait...';
                $redirectUrl = env('WEB_LINK') . '/orders/order_traking/' . $order->id;
                $timer = 3; // 3  seconds

                if($order->source == 'web'){
                    return  view('Paymob.checkout', compact('totalAmount','message','redirectUrl','timer'));
                }
                else{
                    return response()->json([
                        'success' => 'You payment success'
                    ]);
                }
            }
            else {        
                $order = $this->order
                ->where('transaction_id', $data['order'])
                ->first();
                $order->update([
                    'payment_status' => 'faild'
                ]); 
               return  view('Paymob.FaildPayment');
            //    return redirect($appUrl . '://callback_faild');
            }
        }
        else {
               return  view('Paymob.FaildPayment');
        }
             
        return response()->json([
            'success' => 'You payment success'
        ]);
    }

}
