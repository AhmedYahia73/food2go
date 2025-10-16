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

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;

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
    private FinantiolAcounting $financial_account,
    private CheckoutRequest $checkout_request_query){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;

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
        ->where('category_id', null)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        });
        $products = $this->products
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'category_addons' => fn($q) => $q->withLocale($locale),
                'sub_category_addons' => fn($q) => $q->withLocale($locale),
                'excludes' => fn($q) => $q->withLocale($locale),
                'discount', 'extra', 'sales_count', 'tax',
                'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($q) => $q
                        ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                        ->withLocale($locale),
                ]),
                'group_products' => fn($q) => $q
                ->select("id", "name")
                ->with(['products' => fn($q) => $q
                ->select("id", "name")
                ])
            ])
            ->withLocale($locale)
            ->where('item_type', '!=', 'online')
            ->where('status', 1)
            ->whereNotIn('category_id', $category_off)
            // ->whereNotIn('sub_category_id', $category_off)
            ->whereNotIn('products.id', $product_off)
            ->where("favourite", 0)
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {  

                if ($product->stock_type === 'fixed') {
                    $product->count = $product->sales_count->sum('count');
                } elseif ($product->stock_type === 'daily') {
                    $product->count = $product->sales_count
                        ->where('date', date('Y-m-d'))
                        ->sum('count');
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
            'categories' => $categories,
            'products' => $products,  
            'payment_methods' => $paymentMethod, 
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
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'category_addons' => fn($q) => $q->withLocale($locale),
                'sub_category_addons' => fn($q) => $q->withLocale($locale),
                'excludes' => fn($q) => $q->withLocale($locale),
                'discount', 'extra', 'sales_count', 'tax',
                'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($q) => $q
                        ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                        ->withLocale($locale),
                ]),
                'group_products' => fn($q) => $q
                ->select("id", "name")
                ->with(['products' => fn($q) => $q
                ->select("id", "name")
                ])
            ])
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
                    $product->count = $product->sales_count->sum('count');
                } elseif ($product->stock_type === 'daily') {
                    $product->count = $product->sales_count
                        ->where('date', date('Y-m-d'))
                        ->sum('count');
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
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id');
        }])
        ->where('branch_id', $request->branch_id)
        ->get();
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
        $products = ProductResource::collection($products); 

        return response()->json([
            'products' => $products, 
        ]);
    }

    public function cashier_lists(Request $request){
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
        ->where("favourite", 0)
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
            ->with('sub_table:id,table_number,capacity,main_table_id');
        }])
        ->where('branch_id', $request->branch_id)
        ->get();
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products); 

        return response()->json([
            'categories' => $categories,
            'products' => $products, 
            'cafe_location' => $cafe_location,
            'payment_methods' => $paymentMethod, 
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
            ->with('sub_table:id,table_number,capacity,main_table_id');
        }])
        ->where('branch_id', $branch_id)
        ->get(); 
        $financial_account = $this->financial_account
        ->select('id', 'name', 'details', 'logo')
        ->whereHas('branch')
        ->where('status', 1)
        ->get(); 
        $paymentMethod = $this->paymentMethod
        ->where('status', 1)
        ->get();

        return response()->json([
            'cafe_location' => $cafe_location,
            'financial_account' => $financial_account,
            'paymentMethod' => $paymentMethod,
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
            'order_status' => 'required|in:watting,preparing,done,pick_up'
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
        if(!empty($checkout_request)){
            return response()->json([
                'errors' => 'This table is waiting customer to payment'
            ], 400);
        }
        $this->checkout_request_query
        ->where('status', 'done')
        ->delete();
        $this->checkout_request_query
        ->create([
            'table_id' => $request->table_id
        ]);

        return response()->json([
            'success' => 'You send request success'
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
