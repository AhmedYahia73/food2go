<?php

namespace App\Http\Controllers\api\captain_order\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\captain\order\OrderRequest;
use App\Http\Requests\customer\order\OrderRequest as CustomerOrderRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource; 

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSale;
use App\Models\Product;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\KitchenOrder;
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
use App\Models\OrderCart;
use App\Models\Zone;
use App\Models\CheckoutRequest;
use App\Models\FinantiolAcounting;
use App\Models\CashierMan;
use App\Models\Discount;
use App\Models\Bundle;
use App\Models\Kitchen;
use App\Models\CaptainOrder;
use App\Models\ProductOffer;

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;
use App\trait\Notifications;

class CaptainMakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, 
    private CafeLocation $cafe_location, private CafeTable $cafe_table,
    private OrderCart $order_cart, private Zone $zone,
    private FinantiolAcounting $financial_account, private Discount $discount,
    private KitchenOrder $kitchen_order, private CheckoutRequest $checkout_request_query,
    private Kitchen $kitchen, private CashierMan $cashier_man, private Bundle $bundle){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    use Notifications;

    public function captain_orders(Request $request){
        $captain_orders = CaptainOrder::
        where("branch_id", $request->user()->branch_id)
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "captain_orders" => $captain_orders
        ]);
    }

    public function order_void(Request $request){ 
        $validator = Validator::make($request->all(), [
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'exists:order_carts,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $order_cart = $this->order_cart
        ->whereIn('id', $request->cart_ids)
        ->where("prepration_status", "!=", "watting")
        ->first();
        $cashier_man = $this->cashier_man
        ->where('my_id', $request->manager_id)
        ->first();

        if(!empty($order_cart)){
            $validator = Validator::make($request->all(), [  
                'table_id' => 'required|exists:cafe_tables,id',
                'manager_id' => 'required',
                'manager_password' => 'required', 
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            } 
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
        }
 
        $order_cart = $this->order_cart
        ->whereIn('id', $request->cart_ids)
        ->delete();
        $order_cart = $this->order_cart
        ->where('table_id', $request->table_id)
        ->first();
        if(empty($order_cart)){ 
            $cafe_table = $this->cafe_table
            ->where('id', $request->table_id)
            ->update([
                'current_status' => 'not_available_pre_order'
            ]);
        }

        return response()->json([
            'success' => 'you void order success'
        ]);
    }

    public function notification_order(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => ['required', 'exists:cafe_tables,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $cafe_table = $this->cafe_table
        ->where('id', $request->table_id)
        ->with('location:id,name')
        ->first();
        $branch_id = $request->user()->branch_id;
        $users_tokens = $this->cashier_man
        ->where("branch_id", $branch_id)
        ->pluck('fcm_token')
        ->filter()->toArray();
        $body = 'Table ' . $cafe_table->table_number . 
            ' at location ' . $cafe_table?->location?->name . ' Make Order';
  
        $notifications = $this->sendNotificationToMany($users_tokens, $cafe_table->table_number, $body);
     
        return response()->json([
            'success' => 'You send notifications success',
            "notifications" => $notifications->count()
        ]);
    }

    public function preparation_num(Request $request){ 
        $validator = Validator::make($request->all(), [
            'preparation_num' => 'required|numeric|unique:cafe_tables,preparation_num',
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $cafe_table = $this->cafe_table
        ->where("id", $request->table_id)
        ->update([
            "preparation_num" => $request->preparation_num
        ]);

        return response()->json([
            "success" => "You put prepration number success",
            "preparation_num" => $request->preparation_num
        ]);
    }
    
    public function discount_list(Request $request){
        $discount_list = $this->discount
        ->select("id", "name", "type", "amount")
        ->get();

        return response()->json([
            'discount_list' => $discount_list
        ]);
    }

    public function my_lists(Request $request){
        // /captain/my_lists
        
        if($request->user() && $request->user()->branch_id){
            $branch_id = $request->user()->branch_id;
        }
        else{
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|exists:branches,id',
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $branch_id = $request->branch_id;
        }
        $paymentMethod = $this->paymentMethod
        ->where('status', 1)
        ->get();
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
        ->where('status', 1)
        ->orderBy("priority")
        ->where('category_id', null)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        });
        $products = $this->products
        ->orderBy('order')
        ->with([
            'addons' => fn($q) => $q->withLocale($locale),
            'category_addons' => fn($q) => $q->withLocale($locale),
            'sub_category_addons' => fn($q) => $q->withLocale($locale),
            'excludes' => fn($q) => $q->withLocale($locale),  
            "discount" => fn($q) => $q->where(fn($d) => $d->whereJsonContains("module", "pos")->orWhereJsonContains("module", "all"))
            , 'extra', 'tax',
            'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
            'variations' => fn($q) => $q->withLocale($locale)->with([
                'options' => fn($q) => $q
                    ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                    ->withLocale($locale),
            ]),
                'group_products' => fn($q) => $q
                ->with(['products' => fn($q) => $q
                ->select("products.id", "products.name")->withLocale($locale)]),
        ])
        ->withSum('sales_count as sales_count_fixed', 'count')
        ->withSum(['sales_count as sales_count_daily' => function ($query) {
            $query->where('date', date('Y-m-d'));
        }], 'count')
        ->withLocale($locale)
        ->where('item_type', '!=', 'online') 
        ->where('status', 1)
        ->get()
        ->map(function($product) use($category_off, $product_off, $option_off, $branch_id){
        
            if ($product->stock_type === 'fixed') {
                $product->count = $product->sales_count_fixed ?? 0;
            } elseif ($product->stock_type === 'daily') {
                $product->count = $product->sales_count_daily ?? 0;
            }

            $product->in_stock = $product->number > $product->count;

            $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id) {
                $variation->options = $variation->options
                    ->where('status', 1)
                    ->values()
                    ->reject(fn($option) => $option_off->contains($option->id))
                    ->map(function ($option) {
                        $option->price = $option->option_pricing->first()?->price ?? $option->price;
                        return $option;
                    });

                return $variation;
            });

            return $product;
        })->filter();
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
        }])
        ->where('branch_id', $branch_id)
        ->get()
        ->map(function($item){
            $item->tables =  $item?->tables?->map(function($element){
                $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
                $element->makeHidden(['call_payment']);
                return $element; 
            });
            return $item;
        });
        $favourite_products = $this->products 
        ->orderBy('order')
        ->with([
            'addons' => fn($q) => $q->withLocale($locale),
            'category_addons' => fn($q) => $q->withLocale($locale),
            'sub_category_addons' => fn($q) => $q->withLocale($locale),
            'excludes' => fn($q) => $q->withLocale($locale),
            'discount', 'extra', 'tax',
            'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
            'variations' => fn($q) => $q->withLocale($locale)->with([
                'options' => fn($q) => $q
                    ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                    ->withLocale($locale),
            ]),
                'group_products' => fn($q) => $q
                ->with(['products' => fn($q) => $q
                ->select("products.id", "products.name")->withLocale($locale)]),
        ])
        ->withSum('sales_count as sales_count_fixed', 'count')
        ->withSum(['sales_count as sales_count_daily' => function ($query) {
            $query->where('date', date('Y-m-d'));
        }], 'count')
        ->withLocale($locale)
        ->where('item_type', '!=', 'online') 
        ->where("favourite", 1)
        ->where('status', 1)
        ->get()
        ->map(function($product) use($category_off, $product_off, $option_off, $branch_id){
          
            if ($product->stock_type === 'fixed') {
                $product->count = $product->sales_count_fixed ?? 0;
            } elseif ($product->stock_type === 'daily') {
                $product->count = $product->sales_count_daily ?? 0;
            }

            $product->in_stock = $product->number > $product->count;

            $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id) {
                $variation->options = $variation->options
                    ->where('status', 1)
                    ->values()
                    ->reject(fn($option) => $option_off->contains($option->id))
                    ->map(function ($option) {
                        $option->price = $option->option_pricing->first()?->price ?? $option->price;
                        return $option;
                    });

                return $variation;
            });

            return $product;
        })->filter();
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
        }])
        ->where('branch_id', $branch_id)
        ->get()
        ->map(function($item){
            $item->tables =  $item?->tables?->map(function($element){
                $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
                $element->makeHidden(['call_payment']);
                return $element; 
            });
            return $item;
        });
        $products_count = $products->where("weight_status", 0)
        ->values();
        $products_weight = $products->where("weight_status", 1)
        ->values();
        $favourite_products_count = $favourite_products->where("weight_status", 0)
        ->values();
        $favourite_products_weight = $favourite_products->where("weight_status", 1)
        ->values();
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products_count); 
        $favourite_products = ProductResource::collection($favourite_products_count); 
        $favourite_products_weight = ProductResource::collection($favourite_products_weight); 
        $products_weight = ProductResource::collection($products_weight); 

        return response()->json([
            'categories' => $categories,
            'products' => $products, 
            'products_weight' => $products_weight, 
            'favourite_products' => $favourite_products, 
            'favourite_products_weight' => $favourite_products_weight, 
            'cafe_location' => $cafe_location,
            'payment_methods' => $paymentMethod, 
        ]);
    }

    public function product_item(Request $request, $id){
        // /captain/product_item/{id}  
        if($request->user() && $request->user()->branch_id){
            $branch_id = $request->user()->branch_id;
        }
        else{
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|exists:branches,id',
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $branch_id = $request->branch_id;
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get(); 
        $option_off = $branch_off->pluck('option_id')->filter();
        $products = $this->products
        ->orderBy('order')
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'category_addons' => fn($q) => $q->withLocale($locale),
                'sub_category_addons' => fn($q) => $q->withLocale($locale),
                'excludes' => fn($q) => $q->withLocale($locale),
                'discount', 'extra', 'tax',
                'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($q) => $q
                        ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                        ->withLocale($locale),
                ]),
                'tax_module' => fn($q) => $q->whereHas('module', fn($q) => $q->where('branch_id', $branch_id))
                ->with('tax'),
                  'group_products' => fn($q) => $q
                    ->with(['products' => fn($q) => $q
                    ->select("products.id", "products.name")->withLocale($locale)]),
            ])
            ->withSum('sales_count as sales_count_fixed', 'count')
            ->withSum(['sales_count as sales_count_daily' => function ($query) {
                $query->where('date', date('Y-m-d'));
            }], 'count')
            ->withLocale($locale)
            ->where('id', $id)
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {  

                if ($product->stock_type === 'fixed') {
                    $product->count = $product->sales_count_fixed ?? 0;
                } elseif ($product->stock_type === 'daily') {
                    $product->count = $product->sales_count_daily ?? 0;
                }
         
                $tax_module = $product->tax_module->first()?->tax;
                if(!empty($tax_module)){
                    unset($product->tax);
                    $product->tax = $tax_module;
                }
                $product->in_stock = $product->number > $product->count;

                $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id) {
                    $variation->options = $variation->options
                        ->where('status', 1)
                        ->values()
                        ->reject(fn($option) => $option_off->contains($option->id))
                        ->map(function ($option) {
                            $option->price = $option->option_pricing->first()?->price ?? $option->price;
                            return $option;
                        });

                    return $variation;
                });

                return $product;
            });
        $products = ProductResource::collection($products)->toArray(request());

        $products = collect($products)->map(function ($item) {
            return [
                'id' => $item['id'], 
                'name' => $item['name'],
                'description' => $item['description'],
                'category_id' => $item['category_id'],
                'sub_category_id' => $item['sub_category_id'],
                'price' => $item['price'],
                'price_after_discount' => $item['price_after_discount'],
                'price_after_tax' => $item['price_after_tax'],
                'image_link' => $item['image_link'],
                'allExtras' => collect($item['allExtras']),
                'addons' => collect($item['addons']),
                'excludes' => collect($item['excludes'])?->select('id', 'name'),
                'group_products' => collect($item['group_products']),
                'variations' => collect($item['variations'])?->select('id', 'name', 'type', 'min', 'max', 'required', 'options')
            ];
        }); 

        return response()->json([ 
            'products' => $products[0] ?? []
        ]);
    }

    public function product_in_category(Request $request, $id){
        // /captain/product_in_category/{id}
        if($request->user() && $request->user()->branch_id){
            $branch_id = $request->user()->branch_id;
        }
        else{
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|exists:branches,id',
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $branch_id = $request->branch_id;
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter();
        $category_off = $branch_off->where("category_id", $id)->pluck('category_id')->filter();
        $option_off = $branch_off->pluck('option_id')->filter();

        $products = $this->products
        ->orderBy('order')
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'category_addons' => fn($q) => $q->withLocale($locale),
                'sub_category_addons' => fn($q) => $q->withLocale($locale),
                'excludes' => fn($q) => $q->withLocale($locale),
                'discount', 'extra', 'tax',
                'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($q) => $q
                        ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                        ->withLocale($locale),
                ]),
                  'group_products' => fn($q) => $q
                    ->with(['products' => fn($q) => $q
                    ->select("products.id", "products.name")->withLocale($locale)]),
            ])
            ->withSum('sales_count as sales_count_fixed', 'count')
            ->withSum(['sales_count as sales_count_daily' => function ($query) {
                $query->where('date', date('Y-m-d'));
            }], 'count')
            ->withLocale($locale)
            ->where('item_type', '!=', 'online')
            ->where('status', 1)
            ->whereNotIn('category_id', $category_off)
            // ->whereNotIn('sub_category_id', $category_off)
            ->whereNotIn('products.id', $product_off)
            ->where(function($query) use($id){
                $query->where("category_id", $id)
                ->orWhere("sub_category_id", $id);
            })
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {  

                if ($product->stock_type === 'fixed') {
                    $product->count = $product->sales_count_fixed ?? 0;
                } elseif ($product->stock_type === 'daily') {
                    $product->count = $product->sales_count_daily ?? 0;
                }

                $product->in_stock = $product->number > $product->count;

                $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id) {
                    $variation->options = $variation->options
                        ->where('status', 1)
                        ->values()
                        ->reject(fn($option) => $option_off->contains($option->id))
                        ->map(function ($option) {
                            $option->price = $option->option_pricing->first()?->price ?? $option->price;
                            return $option;
                        });

                    return $variation;
                });

                return $product;
            });
        $products = ProductResource::collection($products)->toArray(request());

        $products = collect($products)->map(function ($item) {
            return [
                'id' => $item['id'], 
                'name' => $item['name'],
                'description' => $item['description'],
                'category_id' => $item['category_id'],
                'sub_category_id' => $item['sub_category_id'],
                'price' => $item['price'],
                'price_after_discount' => $item['price_after_discount'],
                'price_after_tax' => $item['price_after_tax'],
                'image_link' => $item['image_link'],
                'allExtras' => collect($item['allExtras']),
                'addons' => collect($item['addons']),
                'excludes' => collect($item['excludes'])?->select('id', 'name'),
                'group_products' => collect($item['group_products']),
                'variations' => collect($item['variations'])?->select('id', 'name', 'type', 'min', 'max', 'required', 'options')
            ];
        }); 

        return response()->json([ 
            'products' => $products,
        ]);
    }

    public function lists(Request $request){
        // /captain/lists
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $paymentMethod = $this->paymentMethod
        ->where('status', 1)
        ->get();
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_id = $request->branch_id;
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
        ->where('status', 1)
        ->orderBy("priority")
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
        }, 'extra', 
        "discount" => fn($q) => $q->where(fn($d) => $d->whereJsonContains("module", "pos")->orWhereJsonContains("module", "all")), 
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
        ->where('item_type', '!=', 'online') 
        ->where("favourite", 1)
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
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
        }])
        ->where('branch_id', $request->branch_id)
        ->get()
        ->map(function($item){
            $item->tables =  $item?->tables?->map(function($element){
                $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
                $element->makeHidden(['call_payment']);
                return $element; 
            });
            return $item;
        });
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products); 

        return response()->json([
            'categories' => $categories,
            'products' => $products, 
            'cafe_location' => $cafe_location,
            'payment_methods' => $paymentMethod, 
        ]);
    }

    public function product_category_lists(Request $request, $id){
        // /captain/product_category_lists/{id}
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $paymentMethod = $this->paymentMethod
        ->where('status', 1)
        ->get();
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_id = $request->branch_id;
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $option_off = $branch_off->pluck('option_id')->filter();

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
        }, 'extra', 
        "discount" => fn($q) => $q->where(fn($d) => $d->whereJsonContains("module", "pos")->orWhereJsonContains("module", "all")), 
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
        ->where('item_type', '!=', 'online') 
        ->where(function($query) use($id){
            $query->where("category_id", $id)
            ->orWhere("sub_category_id", $id);
        })
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
        $products = ProductResource::collection($products); 

        return response()->json([
            'products' => $products, 
        ]);
    }

    // public function cashier_lists(Request $request){
    //     // /captain/lists
    //     $validator = Validator::make($request->all(), [
    //         'branch_id' => 'required|exists:branches,id',
    //         'module' => 'in:take_away,dine_in,delivery'
    //     ]);//tax_modules all,take_away,dine_in,delivery
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     } 
    //     $module = $request->module ?? null;
    //     $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
    //     $branch_id = $request->branch_id;
    //     $branch_off = $this->branch_off
    //     ->where('branch_id', $branch_id)
    //     ->get();
    //     $product_off = $branch_off->pluck('product_id')->filter();
    //     $category_off = $branch_off->pluck('category_id')->filter();
    //     $option_off = $branch_off->pluck('option_id')->filter();

    //     $categories = $this->category
    //     ->with(['sub_categories' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 
    //     'addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }])
    //     ->withLocale($locale)
    //     ->where('status', 1)
    //     ->whereNull('category_id')
    //     ->orderBy("priority") 
    //     ->get()
    //     ->map(function($item) use($category_off, $locale){
    //         $sub_category = clone $item->sub_categories 
    //         ->filter(function($item) use($category_off){
    //             return !$category_off->contains($item->id);
    //         }); 
    //         $item->sub_categories = $sub_category;
    //         return $item;
    //     })
    //     ->filter(function($item) use($category_off){
    //         return !$category_off->contains($item->id);
    //     });
    //     $products = $this->products
    //     ->orderBy('order')
    //     ->with(['addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     },'sub_category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'excludes' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'extra', 'discount', 
    //     'variations' => function($query) use($locale){
    //         $query->withLocale($locale)
    //         ->with(['options' => function($query_option) use($locale){
    //             $query_option
    //             ->where("status", 1)
    //             ->with(['extra' => function($query_extra) use($locale){
    //                 $query_extra->with('parent_extra')
    //                 ->withLocale($locale);
    //             }])
    //             ->withLocale($locale);
    //         }]);
    //     }, 'sales_count', 'tax',
    //     'tax_module' => fn($q) => $q->whereHas('module', fn($q) => $q->where('branch_id', $branch_id)
    //     ->whereIn('app_type', ['pos', 'all']))
    //     ->with('tax'),
    //     'tax_module.module'])
    //     ->withLocale($locale)
    //     ->where('item_type', '!=', 'online') 
    //     ->where('status', 1)
    //     ->get()
    //     ->map(function($product) use($category_off, $product_off, $option_off, $branch_id, $module){
    //         //get count of sales of product to detemine stock
    //         $new_price = $product?->product_pricing
    //         ->where('branch_id', $branch_id)
    //         ->first()?->price;
    //         if(empty($new_price)){
    //             $new_price = $product?->pos_pricing->where('module', $module)
    //             ->first()?->price ?? $product->price;
    //         }
    //         $product->price = $new_price ?? $product->price;
    //         $product->favourite = false;
    //         if ($product->stock_type == 'fixed') {
    //             $product->count = $product->sales_count->sum('count');
    //             $product->in_stock = $product->number > $product->count ? true : false;
    //         }
    //         elseif ($product->stock_type == 'daily') {
    //             $product->count = $product->sales_count
    //             ->where('date', date('Y-m-d'))
    //             ->sum('count');
    //             $product->in_stock = $product->number > $product->count ? true : false;
    //         }
    //         // return !$category_off->contains($item->id);
    //         // $category_off, $product_off, $option_off
    //         if ($category_off->contains($product->category_id) || 
    //         $category_off->contains($product->sub_category_id)
    //         || $product_off->contains($product->id)) {
    //             return null;
    //         }
    //         // Resolve tax before variations map
    //         $resolved_tax = $product->tax_module
    //             ->filter(fn($tm) => $tm->module->where('branch_id', $branch_id)->whereIn('app_type', ['pos', 'all'])->count() > 0)
    //             ->first()?->tax ?? $product->tax ?? null;

    //         $product->variations = $product->variations->map(function ($variation) 
    //         use ($option_off, $product, $branch_id, $resolved_tax) {
    //             $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
    //             $variation->options = $variation->options->map(function($element) use($branch_id, $resolved_tax){
    //                 $element->price = $element?->option_pricing->where('branch_id', $branch_id)
    //                 ->first()?->price ?? $element->price;
    //                 $element->new_tax = $resolved_tax;
    //                 return $element;
    //             });
    //             return $variation;
    //         });
    //         $product->addons = $product->addons->map(function ($addon) use ($product) {
    //             $addon->discount = $product->discount;
    //             return $addon;
    //         });
    //         if (!empty($resolved_tax)) {
    //             $product->tax = $resolved_tax;
    //         }
    //         return $product;
    //     })->filter();
    //     $cafe_location = $this->cafe_location
    //     ->with(['tables' => function($query){
    //         return $query
    //         ->where('status', 1)
    //         ->where('is_merge', 0)
    //         ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
    //     }])
    //     ->where('branch_id', $request->branch_id)
    //     ->get()
    //     ->map(function($item){
    //         $item->tables =  $item?->tables?->map(function($element){
    //             $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
    //             $element->makeHidden(['call_payment']);
    //             return $element; 
    //         });
    //         return $item;
    //     });
    //     $favourite_products = $this->products
    //     ->orderBy('order')
    //     ->with(['addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     },'sub_category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'excludes' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'extra', 'discount', 
    //     'variations' => function($query) use($locale){
    //         $query->withLocale($locale)
    //         ->with(['options' => function($query_option) use($locale){
    //             $query_option->with(['extra' => function($query_extra) use($locale){
    //                 $query_extra->with('parent_extra')
    //                 ->withLocale($locale);
    //             }])
    //             ->withLocale($locale);
    //         }]);
    //     }, 'sales_count', 'tax', 'tax_module.module'])
    //     ->withLocale($locale)
    //     ->where('item_type', '!=', 'online') 
    //     ->where("favourite", 1)
    //     ->where('status', 1)
    //     ->get()
    //     ->map(function($product) use($category_off, $product_off, $option_off, $branch_id, $module){
    //         //get count of sales of product to detemine stock
            
    //         $tax_module = $product?->tax
    //         ?->tax_module
    //         ?->map(function ($taxItem) use ($module, $branch_id, $product) {

    //             $isFound = $taxItem->module
	// 			->where('module', $module) 
    //             ->whereIn('app_type', ['pos', 'all'])
    //             ->Where("branch_id", $branch_id)
	// 			->first();
	// 			if($isFound){
	// 				return $product?->tax;
	// 			}

    //         })
    //         ->filter()
    //         ->first();
    //         if(!empty($tax_module)){  
    //             $product->tax = $tax_module;
    //         }
    //         else{
    //             $product->tax = null;
    //         }
    //         $new_price = $product?->product_pricing
    //         ->where('branch_id', $branch_id)
    //         ->first()?->price;
    //         if(empty($new_price)){
    //             $new_price = $product?->pos_pricing->where('module', $module)
    //             ->first()?->price ?? $product->price;
    //         }
    //         $product->price = $new_price ?? $product->price;
    //         $product->favourite = false;
    //         if ($product->stock_type == 'fixed') {
    //             $product->count = $product->sales_count->sum('count');
    //             $product->in_stock = $product->number > $product->count ? true : false;
    //         }
    //         elseif ($product->stock_type == 'daily') {
    //             $product->count = $product->sales_count
    //             ->where('date', date('Y-m-d'))
    //             ->sum('count');
    //             $product->in_stock = $product->number > $product->count ? true : false;
    //         }
    //         // return !$category_off->contains($item->id);
    //         // $category_off, $product_off, $option_off
    //         if ($category_off->contains($product->category_id) || 
    //         $category_off->contains($product->sub_category_id)
    //         || $product_off->contains($product->id)) {
    //             return null;
    //         }
    //         $product->variations = $product->variations->map(function ($variation) 
    //         use ($option_off, $product, $branch_id, $tax_module) {
    //             $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
    //             $variation->options = $variation->options->map(function($element) use($branch_id, $product, $tax_module){
    //                 $element->price = $element?->option_pricing->where('branch_id', $branch_id)
    //                 ->first()?->price ?? $element->price;
    //                 $element->new_tax = $tax_module ?? $product->tax;
    //                 return $element;
    //             });
              
    //             return $variation;
    //         });
    //         $product->addons = $product->addons->map(function ($addon) 
    //         use ($product) {
    //             $addon->discount = $product->discount;
              
    //             return $addon;
    //         });
         
    //         $tax_module = $product?->tax
    //         ?->tax_module
    //         ?->map(function ($taxItem) use ($module, $branch_id, $product) {

    //             $isFound = $taxItem->module
	// 			->where('module', $module) 
    //             ->whereIn('app_type', ['pos', 'all'])
    //             ->Where("branch_id", $branch_id)
	// 			->first();
	// 			if($isFound){
	// 				return $product?->tax;
	// 			}

    //         })
    //         ->filter()
    //         ->first();
    //         if(!empty($tax_module)){  
    //             $product->tax = $tax_module;
    //         }
    //         else{
    //             $product->tax = null;
    //         }
    //         return $product;
    //     })->filter();
    //     $cafe_location = $this->cafe_location
    //     ->with(['tables' => function($query){
    //         return $query
    //         ->where('status', 1)
    //         ->where('is_merge', 0)
    //         ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
    //     }])
    //     ->where('branch_id', $request->branch_id)
    //     ->get()
    //     ->map(function($item){
    //         $item->tables =  $item?->tables?->map(function($element){
    //             $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
    //             $element->makeHidden(['call_payment']);
    //             return $element; 
    //         });
    //         return $item;
    //     });
    //     $products_count = $products->where("weight_status", 0)
    //     ->values();
    //     $products_weight = $products->where("weight_status", 1)
    //     ->values();
    //     $favourite_products_count = $favourite_products->where("weight_status", 0)
    //     ->values();
    //     $favourite_products_weight = $favourite_products->where("weight_status", 1)
    //     ->values();
    //     $categories = CategoryResource::collection($categories);
    //     $products = ProductResource::collection($products_count); 
    //     $favourite_products = ProductResource::collection($favourite_products_count); 
    //     $favourite_products_weight = ProductResource::collection($favourite_products_weight); 
    //     $products_weight = ProductResource::collection($products_weight); 
    //     $discounts = $this->discount
    //     ->get()
    //     ->map(function($item){
    //         return [
    //             "id" => $item->id,
    //             "name" => $item->name,
    //             "type" => $item->type,
    //             "amount" => $item->amount,
    //         ];
    //     });
    //     $bundles = $this->bundle
    //     ->where("status", 1)
    //     ->with("products", "discount", "tax", "translations")
    //     ->get()
    //     ->map(function($item) use($locale){
    //         return [
    //             "id" => $item->id,
    //             "name" => $item->translations
    //             ->where('key', $item->name)
    //             ->where("locale", $locale)
    //             ->first()?->value ?? $item->name,
    //             "image" => $item->image_link,
    //             "price" => $item->price,
    //             "discount" => [
    //                 "name" => $item?->discount?->name ?? null,
    //                 "type" => $item?->discount?->type ?? null,
    //                 "amount" => $item?->discount?->amount ?? null,
    //             ],
    //             "tax" => [
    //                 "name" => $item?->tax?->name ?? null,
    //                 "type" => $item?->tax?->type ?? null,
    //                 "amount" => $item?->tax?->amount ?? null,
    //             ],
    //             "products" => $item->products
    //             ->map(function($element) use($locale, $item){
    //                 return [
    //                     "id" => $element->id,
    //                     "name" => $element->translations
    //                     ->where('key', $element->name)
    //                     ->where("locale", $locale)
    //                     ->first()?->value ?? $element->name,
    //                     "variations" => $element->variations
    //                     ->map(function($value) use($element, $item, $locale){
    //                         return [
    //                             "id" => $value->id,
    //                             "variation_selected" => $item->bundle_variations
    //                             ->where("product_id", $element->id)
	// 							->first()
    //                             ? 1 : 0,
    //                             "variation" => $value->translations
    //                             ->where('key', $value->name)
    //                             ->where("locale", $locale)
    //                             ->first()?->value ?? $value->name, 
    //                             "type" => $value?->type,
    //                             "min" => $value?->min,
    //                             "max" => $value?->max,
    //                             "required" => $value?->required,
    //                             "options" => $value?->options
    //                             ->map(function($new_item) use($item, $locale){
    //                                 return [
    //                                     "id" => $new_item->id,
    //                                     "name" => $new_item->translations
    //                                     ->where('key', $new_item->name)
    //                                     ->where("locale", $locale)
    //                                     ->first()?->value ?? $new_item->name, 
    //                                     "price" => $new_item->price,
    //                                     "selected" => $new_item->bundle_options
    //                                     ->where("bundle_id", $item->id)
	// 									->first()
    //                                     ? 1 : 0,
    //                                 ];
    //                             }),
    //                         ];
    //                     })
    //                 ]; 
    //             //________________________
    //             }), 
    //         ];
    //     });
    //     $offers_take_away = ProductOffer::
    //     where("start_date", "<=", date("Y-m-d"))
    //     ->where("end_date", ">=", date("Y-m-d"))
    //     ->where("time_from", "<=", date("H:i:s"))
    //     ->where("time_to", ">=", date("H:i:s"))
    //     ->with(["products.addons" => function($query) use($locale){
    //         $query->withLocale($locale);
    //     },'products.sub_category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.excludes' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.extra', 'products.discount', 
    //     'products.variations' => function($query) use($locale){
    //         $query->withLocale($locale)
    //         ->with(['options' => function($query_option) use($locale){
    //             $query_option->with(['extra' => function($query_extra) use($locale){
    //                 $query_extra->with('parent_extra')
    //                 ->withLocale($locale);
    //             }])
    //             ->withLocale($locale);
    //         }]);
    //     }, 'products.sales_count', 'products.tax', 'products.tax_module.module'])
    //     ->where(function($query){
    //         $query
    //         ->whereJsonContains("days", date("l"))
    //         ->orWhere("delay", 1);
    //     })
    //     ->whereJsonContains("module", "take_away")
    //     ->get()
    //     ->map(function($item) use($locale){
    //         $discount = [ 
    //             'name' => $item->name,
    //             'type' => "precentage",
    //             'amount' => $item->discount,
    //         ];
    //         $total = 0;
    //         $products= $item->products
    //         ->map(function($element) use($discount, &$total){
    //             $element->discount = (object) $discount;
    //             $total += $element->price - ($discount['amount'] * $element->price / 100);
    //             return $element;
    //         });
    //         return [
    //             "name" => $item->name,
    //             "total" => $total,
    //             "discount" => $item->discount,
    //             "products" => ProductResource::collection($products),
    //         ];
    //     });
    //     $offers_dine_in = ProductOffer::
    //     where("start_date", "<=", date("Y-m-d"))
    //     ->where("end_date", ">=", date("Y-m-d"))
    //     ->where("time_from", "<=", date("H:i:s"))
    //     ->where("time_to", ">=", date("H:i:s"))
        
    //     ->with(["products.addons" => function($query) use($locale){
    //         $query->withLocale($locale);
    //     },'products.sub_category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.excludes' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.extra', 'products.discount', 
    //     'products.variations' => function($query) use($locale){
    //         $query->withLocale($locale)
    //         ->with(['options' => function($query_option) use($locale){
    //             $query_option->with(['extra' => function($query_extra) use($locale){
    //                 $query_extra->with('parent_extra')
    //                 ->withLocale($locale);
    //             }])
    //             ->withLocale($locale);
    //         }]);
    //     }, 'products.sales_count', 'products.tax', 'products.tax_module.module'])
    //     ->where(function($query){
    //         $query
    //         ->whereJsonContains("days", date("l"))
    //         ->orWhere("delay", 1);
    //     })
    //     ->whereJsonContains("module", "dine_in")
    //     ->get()
    //     ->map(function($item) use($locale){
    //         $discount = [ 
    //             'name' => $item->name,
    //             'type' => "precentage",
    //             'amount' => $item->discount,
    //         ];
    //         $total = 0;
    //         $products= $item->products
    //         ->map(function($element) use($discount, &$total){
    //             $element->discount = (object) $discount;
    //             $total += $element->price - ($discount['amount'] * $element->price / 100);
    //             return $element;
    //         });
    //         return [
    //             "name" => $item->name,
    //             "total" => $total,
    //             "discount" => $item->discount,
    //             "products" => ProductResource::collection($products),
    //         ];
    //     });
    //     $offers_delivery = ProductOffer::
    //     where("start_date", "<=", date("Y-m-d"))
    //     ->where("end_date", ">=", date("Y-m-d"))
    //     ->where("time_from", "<=", date("H:i:s"))
    //     ->where("time_to", ">=", date("H:i:s"))
        
    //     ->with(["products.addons" => function($query) use($locale){
    //         $query->withLocale($locale);
    //     },'products.sub_category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.excludes' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'products.extra', 'products.discount', 
    //     'products.variations' => function($query) use($locale){
    //         $query->withLocale($locale)
    //         ->with(['options' => function($query_option) use($locale){
    //             $query_option->with(['extra' => function($query_extra) use($locale){
    //                 $query_extra->with('parent_extra')
    //                 ->withLocale($locale);
    //             }])
    //             ->withLocale($locale);
    //         }]);
    //     }, 'products.sales_count', 'products.tax', 'products.tax_module.module'])
    //     ->where(function($query){
    //         $query
    //         ->whereJsonContains("days", date("l"))
    //         ->orWhere("delay", 1);
    //     })
    //     ->whereJsonContains("module", "delivery")
    //     ->get()
    //     ->map(function($item) use($locale){
    //         $discount = [ 
    //             'name' => $item->name,
    //             'type' => "precentage",
    //             'amount' => $item->discount,
    //         ];
    //         $total = 0;
    //         $products= $item->products
    //         ->map(function($element) use($discount, &$total){
    //             $element->discount = (object) $discount;
    //             $total += $element->price - ($discount['amount'] * $element->price / 100);
    //             return $element;
    //         });
    //         return [
    //             "name" => $item->name,
    //             "total" => $total,
    //             "discount" => $item->discount,
    //             "products" => ProductResource::collection($products),
    //         ];
    //     });
    //     return response()->json([
    //         'categories' => $categories,
    //         'products' => ProductResource::collection($products), 
    //         'products_weight' => $products_weight, 
    //         'favourite_products' => $favourite_products, 
    //         'favourite_products_weight' => $favourite_products_weight, 
    //         'cafe_location' => $cafe_location,
    //         'discounts' => $discounts,
    //         'bundles' => $bundles,
    //         'offers_take_away' => $offers_take_away,
    //         'offers_dine_id' => $offers_dine_in,
    //         'offers_delivery' => $offers_delivery,
    //     ]);
    // }
    
    public function cashier_lists(Request $request) {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'module' => 'in:take_away,dine_in,delivery'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        // 2. Initial Setup & Optimization Variables
        $module = $request->module ?? null;
        $locale = $request->locale ?? $request->query('locale', app()->getLocale());
        $branch_id = $request->branch_id;
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $current_day = date('l');

        // Get exclusions in one query
        $branch_off = $this->branch_off->where('branch_id', $branch_id)->get();
        $product_off = $branch_off->pluck('product_id')->filter()->toArray();
        $category_off = $branch_off->pluck('category_id')->filter()->toArray();
        $option_off = $branch_off->pluck('option_id')->filter()->toArray();

        // 3. Fetch Categories
        $categories = $this->category
            ->with([
                'sub_categories' => fn($q) => $q->withLocale($locale), 
                'addons' => fn($q) => $q->withLocale($locale)
            ])
            ->withLocale($locale)
            ->where('status', 1)
            ->whereNull('category_id')
            ->orderBy("priority") 
            ->get()
            ->each(function($item) use ($category_off) {
                $item->setRelation('sub_categories', $item->sub_categories->reject(fn($sub) => in_array($sub->id, $category_off)));
            })
            ->reject(fn($item) => in_array($item->id, $category_off));

        // 4. Shared Product Processor Method (Optimized to run everything in-memory)
        $processProducts = function($productsQuery) use ($category_off, $product_off, $option_off, $branch_id, $module, $today) {
            return $productsQuery->get()->map(function($product) use ($category_off, $product_off, $option_off, $branch_id, $module, $today) {
                // Exclude early before heavy calculations
                if (in_array($product->category_id, $category_off) || 
                    in_array($product->sub_category_id, $category_off) || 
                    in_array($product->id, $product_off)) {
                    return null;
                }

                // Resolve Pricing
                $new_price = $product->product_pricing->firstWhere('branch_id', $branch_id)?->price;
                if (empty($new_price)) {
                    $new_price = $product->pos_pricing->firstWhere('module', $module)?->price ?? $product->price;
                }
                $product->price = $new_price;
                $product->favourite = false;

                // Stock Calculation (Using in-memory collections instead of queries)
                if ($product->stock_type == 'fixed') {
                    $product->count = $product->sales_count->sum('count');
                    $product->in_stock = $product->number > $product->count;
                } elseif ($product->stock_type == 'daily') {
                    $product->count = $product->sales_count->where('date', $today)->sum('count');
                    $product->in_stock = $product->number > $product->count;
                }

                // Resolve Tax Module efficiently
                $resolved_tax = $product->tax_module->first()?->tax ?? $product->tax ?? null;

                // Map Variations & Options
                $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id, $resolved_tax) {
                    $variation->options = $variation->options
                        ->reject(fn($option) => in_array($option->id, $option_off))
                        ->map(function($element) use ($branch_id, $resolved_tax) {
                            $element->price = $element->option_pricing->firstWhere('branch_id', $branch_id)?->price ?? $element->price;
                            $element->new_tax = $resolved_tax;
                            return $element;
                        });
                    return $variation;
                });

                // Map Addons
                $product->addons = $product->addons->map(function ($addon) use ($product) {
                    $addon->discount = $product->discount;
                    return $addon;
                });

                if (!empty($resolved_tax)) {
                    $product->tax = $resolved_tax;
                }

                return $product;
            })->filter();
        };

        // 5. Build Base Product Query with all Eager Loads to avoid N+1
        $baseProductQuery = $this->products
            ->orderBy('order')
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'sub_category_addons' => fn($q) => $q->withLocale($locale), 
                'category_addons' => fn($q) => $q->withLocale($locale), 
                'excludes' => fn($q) => $q->withLocale($locale), 
                'extra', 'sales_count', 'tax', 'product_pricing', 'pos_pricing',
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($qo) => $qo->where("status", 1)->withLocale($locale)->with([
                        'extra' => fn($qe) => $qe->with('parent_extra')->withLocale($locale),
                        'option_pricing'
                    ])
                ]),
                'tax_module' => fn($q) => $q->whereHas('module', fn($qm) => $qm->where('branch_id', $branch_id)->whereIn('app_type', ['pos', 'all']))
                                            ->with(['tax', 'module']),
                "discount" => fn($q) => $q->where(fn($d) => $d->whereJsonContains("module", "pos")->orWhereJsonContains("module", "all"))
            ])
            ->withLocale($locale)
            ->where('item_type', '!=', 'online') 
            ->where('status', 1);

        // Execute product pipelines
        $allProcessedProducts = $processProducts(clone $baseProductQuery);
        $allProcessedFavourites = $processProducts(clone $baseProductQuery->where("favourite", 1));

        // 6. Partition Products by Weight Status (In-memory, avoids extra queries)
        $products_count = $allProcessedProducts->where("weight_status", 0)->values();
        $products_weight = $allProcessedProducts->where("weight_status", 1)->values();
        
        $favourite_products_count = $allProcessedFavourites->where("weight_status", 0)->values();
        $favourite_products_weight = $allProcessedFavourites->where("weight_status", 1)->values();

        // 7. Fetch Cafe Locations & Tables
        $cafe_location = $this->cafe_location
            ->with(['tables' => fn($q) => $q->where('status', 1)->where('is_merge', 0)->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment')])
            ->where('branch_id', $branch_id)
            ->get()
            ->map(function($item) {
                $item->tables = $item->tables->map(function($element) {
                    $element->call_payment_status = $element->call_payment->isNotEmpty();
                    $element->makeHidden(['call_payment']);
                    return $element; 
                });
                return $item;
            });

        // 8. Fetch Discounts & Bundles
        $discounts = $this->discount->get(['id', 'name', 'type', 'amount']);
        
        $bundles = $this->bundle
            ->where("status", 1)
            ->with(["products.translations", "products.variations.translations", "products.variations.options.translations", "discount", "tax", "translations", "bundle_variations", "bundle_options"])
            ->get()
            ->map(function($item) use ($locale) {
                return [
                    "id" => $item->id,
                    "name" => $item->translations->where('key', $item->name)->where("locale", $locale)->first()?->value ?? $item->name,
                    "image" => $item->image_link,
                    "price" => $item->price,
                    "discount" => [
                        "name" => $item->discount?->name ?? null,
                        "type" => $item->discount?->type ?? null,
                        "amount" => $item->discount?->amount ?? null,
                    ],
                    "tax" => [
                        "name" => $item->tax?->name ?? null,
                        "type" => $item->tax?->type ?? null,
                        "amount" => $item->tax?->amount ?? null,
                    ],
                    "products" => $item->products->map(function($element) use ($locale, $item) {
                        return [
                            "id" => $element->id,
                            "name" => $element->translations->where('key', $element->name)->where("locale", $locale)->first()?->value ?? $element->name,
                            "variations" => $element->variations->map(function($value) use ($element, $item, $locale) {
                                return [
                                    "id" => $value->id,
                                    "variation_selected" => $item->bundle_variations->where("product_id", $element->id)->isNotEmpty() ? 1 : 0,
                                    "variation" => $value->translations->where('key', $value->name)->where("locale", $locale)->first()?->value ?? $value->name, 
                                    "type" => $value->type,
                                    "min" => $value->min,
                                    "max" => $value->max,
                                    "required" => $value->required,
                                    "options" => $value->options->map(function($new_item) use ($item, $locale) {
                                        return [
                                            "id" => $new_item->id,
                                            "name" => $new_item->translations->where('key', $new_item->name)->where("locale", $locale)->first()?->value ?? $new_item->name, 
                                            "price" => $new_item->price,
                                            "selected" => $item->bundle_options->where("bundle_id", $item->id)->where('id', $new_item->id)->isNotEmpty() ? 1 : 0,
                                        ];
                                    }),
                                ];
                            })
                        ]; 
                    }), 
                ];
            });

        // 9. Shared Offers Processor
        $getOffersByModule = function($moduleName) use ($locale, $today, $current_time, $current_day) {
            return ProductOffer::where("start_date", "<=", $today)
                ->where("end_date", ">=", $today)
                ->where("time_from", "<=", $current_time)
                ->where("time_to", ">=", $current_time)
                ->whereJsonContains("module", $moduleName)
                ->where(fn($q) => $q->whereJsonContains("days", $current_day)->orWhere("delay", 1))
                ->with([
                    "products.addons" => fn($q) => $q->withLocale($locale),
                    "products.sub_category_addons" => fn($q) => $q->withLocale($locale), 
                    "products.category_addons" => fn($q) => $q->withLocale($locale), 
                    "products.excludes" => fn($q) => $q->withLocale($locale), 
                    "products.extra", "products.discount", "products.sales_count", "products.tax", "products.tax_module.module",
                    "products.variations" => fn($q) => $q->withLocale($locale)->with(['options' => fn($qo) => $qo->with(['extra' => fn($qe) => $qe->with('parent_extra')->withLocale($locale)])->withLocale($locale)])
                ])
                ->get()
                ->map(function($item) {
                    $discount = ['name' => $item->name, 'type' => "precentage", 'amount' => $item->discount];
                    $total = 0;
                    $products = $item->products->map(function($element) use ($discount, &$total) {
                        $element->discount = (object) $discount;
                        $total += $element->price - ($discount['amount'] * $element->price / 100);
                        return $element;
                    });
                    return [
                        "name" => $item->name,
                        "total" => $total,
                        "discount" => $item->discount,
                        "products" => ProductResource::collection($products),
                    ];
                });
        };

        // Execute offers pipelines
        $offers_take_away = $getOffersByModule("take_away");
        $offers_dine_in = $getOffersByModule("dine_in");
        $offers_delivery = $getOffersByModule("delivery");

        // 10. Response
        return response()->json([
            'categories' => CategoryResource::collection($categories),
            'products' => ProductResource::collection($products_count), 
            'products_weight' => ProductResource::collection($products_weight), 
            'favourite_products' => ProductResource::collection($favourite_products_count), 
            'favourite_products_weight' => ProductResource::collection($favourite_products_weight), 
            'cafe_location' => $cafe_location,
            'discounts' => $discounts,
            'bundles' => $bundles,
            'offers_take_away' => $offers_take_away,
            'offers_dine_id' => $offers_dine_in,
            'offers_delivery' => $offers_delivery,
        ]);
    }

    public function get_table_status(){
        $table_status = [
            'available',
            'not_available_pre_order',
            'not_available_with_order',
            'not_available_but_checkout',
            'reserved',
        ];

        return response()->json([
            'table_status' => $table_status
        ]);
    }

    public function my_selection_lists(Request $request){
        if($request->user() && $request->user()->branch_id){
            $branch_id = $request->user()->branch_id;
        }
        else{
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|exists:branches,id',
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $branch_id = $request->branch_id;
        }
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
        }])
        ->where('branch_id', $branch_id)
        ->get()
        ->map(function($item){
            $item->tables =  $item?->tables?->map(function($element){
                $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
                $element->makeHidden(['call_payment']);
                return $element; 
            });
            return $item;
        });
        $financial_account = $this->financial_account
        ->select('id', 'name', 'details', 'logo', 'description_status', 'discount')
        ->whereHas('branch')
        ->where('status', 1)
        ->get(); 

        return response()->json([
            'cafe_location' => $cafe_location,
            'financial_account' => $financial_account,
        ]);
    }

    public function zones_list(){
        // /captain/zones_list
        $zones = $this->zone
        ->get();

        return response()->json([
            'zones' => $zones
        ]);
    }
// ________________________________________________

    public function dine_in_order(CustomerOrderRequest $request){
        // /cashier/dine_in_order
        // Keys
        // amount, total_tax, total_discount, table_id
        // notes
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}], order_status

        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
            'order_status' => 'required|in:watting,preparing,done,preparation,pick_up'
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
            'captain_id' =>$request->user()->id, 
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
        if($request->order_status == "preparing"){
            $kitchen_order = [];
            $kitchen_items = [];
            $order_cart = $order['payment'];  
            $preparing = $order_cart->cart;
            $order_cart->prepration_status = "preparing";  
            $order_cart->save();
            $order_item = $this->dine_in_print($order_cart);
            $order_item = collect($order_item);

            $element = $order_item[0];
            $kitchen_order = [];
            $kitchen = $this->kitchen
            ->where(function($q) use($element){
                $q->whereHas('products', function($query) use ($element){
                    $query->where('products.id', $element['id']);
                })
                ->orWhereHas('category', function($query) use ($element){
                    $query->where('categories.id', $element['category_id'])
                    ->orWhere('categories.id', $element['sub_category_id']);
                });
            })
            ->where('branch_id', $request->user()->branch_id)
            ->first(); 
            if(!empty($kitchen)){
                $kitchen_items[$kitchen->id] = $kitchen;
                $kitchen_order[$kitchen->id][] = $element;
            }
            foreach ($kitchen_order as $key => $item) {
                $this->kitchen_order
                ->create([
                    'table_id' => $request->table_id,
                    'kitchen_id' => $key,
                    'order' => json_encode($item),
                    'type' => 'dine_in',
                    'cart_id' => $order_cart->id,
                ]);
                $kitchen_items[$key]['order'] = $item[0];
            }
        }
  
        return response()->json([
            'success' => $order_data, 
        ]);
    }

    public function checkout_request(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $checkout_request = $this->checkout_request_query
        ->where('status', 'waiting')
        ->where('table_id', $request->table_id)
        ->first(); 
        $this->checkout_request_query
        ->where('status', 'done')
        ->delete();
        $this->checkout_request_query
        ->create([
            'table_id' => $request->table_id
        ]);

        $cafe_table = $this->cafe_table
        ->where('id', $request->table_id)
        ->with('location:id,name')
        ->first();
        $branch_id = $request->user()->branch_id;
        $users_tokens = $this->cashier_man
        ->where("branch_id", $branch_id)
        ->pluck('fcm_token')
        ->filter()->toArray();
        $body = 'Table ' . $cafe_table->table_number . 
            ' at location ' . $cafe_table?->location?->name . ' Call Payment';
  
        $notifications = $this->sendNotificationToMany($users_tokens, $cafe_table->table_number, $body);
      
        return response()->json([
            'success' => 'You send request success',
            "notifications" => $notifications->count(),
        ]);
    }
  
    // ____________________________________________

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
}
