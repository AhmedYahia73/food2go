<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Http;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Banner;
use App\Models\Setting;
use App\Models\Translation;
use App\Models\BranchOff;
use App\Models\ScheduleSlot;
use App\Models\Address;
use App\Models\MainData;
use App\Models\MenueImage;
use App\Models\Policy;
use App\Models\SmsBalance;

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user,
    private Product $product, private Banner $banner, private Setting $settings,
    private Translation $translations, private BranchOff $branch_off,
    private Address $address, private ScheduleSlot $schedule_list,
    private MainData $main_data, private Policy $policies,
    private MenueImage $menue_image, private SmsBalance $sms_balance){}

    public function mainData(){
        // https://bcknd.food2go.online/customer/main_data
        $main_data = $this->main_data
        ->orderByDesc('id')
        ->first();
        if (!empty($main_data)) {
            $main_data->base = url('/');
            $main_data->ar_name = $main_data->translations()
            ->where('locale', 'ar')->where('key', $main_data->name)
            ->first()?->value ?? null;
        }

        return response()->json([
            'main_data' => $main_data
        ]);
    }

    public function policies(){
        // https://bcknd.food2go.online/customer/home/policies
        $policies = $this->policies
        ->orderByDesc('id')
        ->first(); 

        return response()->json([
            'policies' => $policies
        ]);
    }

    public function products(Request $request){
        // https://bcknd.food2go.online/customer/home
        // Keys
        // address_id, branch_id
        
        // // _______________________________________________________________________
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        
        $branch_id = 0;
        if ($request->branch_id && !empty($request->branch_id)) {
            $branch_id = $request->branch_id;
        }
        if ($request->address_id && !empty($request->address_id)) {
            $address = $this->address
            ->where('id', $request->address_id)
            ->first();
            $branch_id = $address?->zone?->branch_id;
        }
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
        if ($request->user_id) {
            $user_id = $request->user_id;
            $products = $this->product
            ->with([ 
                'favourite_product' => fn($q) => $q->where('users.id', $user_id),
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
            ])
            ->withLocale($locale)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1)
            ->whereNotIn('category_id', $category_off)
            // ->whereNotIn('sub_category_id', $category_off)
            ->whereNotIn('products.id', $product_off)
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {
                $product->favourite = $product->favourite_product->isNotEmpty();

                $product->price = $product->product_pricing->first()?->price ?? $product->price;

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
                        ->reject(fn($option) => $option_off->contains($option->id))
                        ->map(function ($option) {
                            $option->price = $option->option_pricing->first()?->price ?? $option->price;
                            return $option;
                        });

                    return $variation;
                });

                return $product;
            });

        }
        else{
                $products = $this->product
                ->with([
                    'addons' => fn($q) => $q->withLocale($locale),
                    'category_addons' => fn($q) => $q->withLocale($locale),
                    'sub_category_addons' => fn($q) => $q->withLocale($locale),
                    'excludes' => fn($q) => $q->withLocale($locale),
                    'discount', 'extra', 'sales_count', 'tax',
                    'variations' => fn($q) => $q->with([
                        'options' => fn($oq) => $oq->with(['option_pricing']) // تأكد دي مطلوبة
                    ])->withLocale($locale),
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
        }
        $discounts = $this->product
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
            'products' => $products,
            'discounts' => $discounts,
            'resturant_time' => $resturant_time,
            'tax' => $tax,
        ]);
    }

    public function web_products(Request $request){
        // https://bcknd.food2go.online/customer/home/web_products
        // Keys
        // address_id, branch_id
        
        // _______________________________________________________________________
        // $response = Http::get('https://clientbcknd.food2go.online/admin/v1/my_sms_package')->body();
        // $response = json_decode($response);
  
        // $sms_subscription = collect($response?->user_sms) ?? collect([]); 
        // $sms_subscription = $sms_subscription->where('back_link', url(''))
        // ->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
        // ->first();
        // $msg_number = $this->sms_balance
        // ->where('package_id', $sms_subscription?->id)
        // ->first();
        // if (!empty($sms_subscription) && empty($msg_number)) {
        //     $msg_number = $this->sms_balance
        //     ->create([
        //         'package_id' => $sms_subscription->id,
        //         'balance' => $sms_subscription->msg_number,
        //     ]);
        // }
        // if (empty($sms_subscription) || $msg_number->balance <= 0) {
        //     $customer_login = $this->settings
        //     ->where('name', 'customer_login')
        //     ->first();
        //     if(empty($customer_login)){
        //         $this->settings
        //         ->create([
        //             'name' => 'customer_login',
        //             'setting' => '{"login":"otp","verification":"email"}',
        //         ]);
        //     }
        //     else{
        //         $customer_login->update([
        //             'setting' => '{"login":"otp","verification":"email"}',
        //         ]);
        //     }
        // }
        // _________________________________________________________________________
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_id = 0;
        if ($request->branch_id && !empty($request->branch_id)) {
            $branch_id = $request->branch_id;
        }
        if ($request->address_id && !empty($request->address_id)) {
            $address = $this->address
            ->where('id', $request->address_id)
            ->first();
            $branch_id = $address?->zone?->branch_id;
        }
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $option_off = $branch_off->pluck('option_id')->filter();
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
        $categories = $this->categories
        ->orderBy('priority')
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->withLocale($locale)
        ->where('status', 1)
        ->where('category_id', null)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        });
    
        if ($request->user_id) {
            $user_id = $request->user_id;
            $products = $this->product
            ->with(['addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 
            'discount', 'favourite_product' => function($query) use($user_id){
                $query->where('users.id', $user_id);
            }
            ,'sub_category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'extra',
            'variations' => function($query) use($locale){
                $query->withLocale($locale)
                ->with(['options']);
            }, 'sales_count', 'tax'])
            ->withLocale($locale)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1)
            ->whereNotIn('category_id', $category_off)
            //->whereNotIn('sub_category_id', $category_off)
            ->whereNotIn('id', $product_off)
            ->get()
            ->map(function($item) use($category_off, $product_off, $option_off, $branch_id){ 
                
                $item->price = $item?->product_pricing?->where('branch_id', $branch_id)
                ->first()?->price ?? $item->price;
                $item->setAttribute('favourite', $item->favourite_product->isNotEmpty());
                $item->variations = $item->variations->map(function ($variation) use ($option_off, $branch_id) {
                    $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                    $variation->options = $variation->options->map(function($element) use($branch_id){
                        $element->price = $element?->option_pricing?->where('branch_id', $branch_id)
                        ->first()?->price ?? $element->price;
                        return $element;
                    });
                    return $variation;
                });
                return $item;
            })->filter();
        }
        else{
            $products = $this->product
            ->with(['addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 
            'discount'
            ,'sub_category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'extra',
            'variations' => function($query) use($locale){
                $query->withLocale($locale)
                ->with(['options']);
            }, 'sales_count', 'tax'])
            ->withLocale($locale)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1)
            ->get()
            ->map(function($product) use($category_off, $product_off, $option_off, $branch_id){ 
        
                $product->price = $product?->product_pricing->where('branch_id', $branch_id)
                ->first()?->price ?? $product->price;
                if ($category_off->contains($product->category_id) || 
                $category_off->contains($product->sub_category_id)
                || $product_off->contains($product->id)) {
                    return null;
                }
                $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id) {
                    $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                    $variation->options = $variation->options->map(function($element) use($branch_id){
                        $element->price = $element?->option_pricing->where('branch_id', $branch_id)
                        ->first()?->price ?? $element->price;
                        return $element;
                    });
                    return $variation;
                });
                return $product;
            })->filter();
        }
            
        $discounts = $this->product
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
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products);

        return response()->json([
            'categories' => $categories,
            'products' => $products,
            'discounts' => $discounts,
            'resturant_time' => $resturant_time,
            'tax' => $tax,
        ]);
    }

    public function schedule_list(Request $request){
        $locale = $request->locale ?? 'en';
        $schedule_list = $this->schedule_list
        ->where('status', 1)
        ->get()
        ->map(function($item) use($locale){
            return [
                'id' => $item->id,
                'name' => $item->translations->where('key', $item->name)
                ->where('locale', $locale)->first()?->value ?? $item->name,
            ];
        });

        return response()->json([
            'schedule_list' => $schedule_list,
        ]);
    }

    public function fav_products(Request $request){
        // https://bcknd.food2go.online/customer/home/fav_products
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation   
        $user_id = $request->user_id;
        $products = $this->product
        ->with([ 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'excludes' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'extra' => function($query) use($locale){
            $query->whereNull('option_id')
            ->withLocale($locale);
        }, 'discount','sub_category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'favourite_product' => function($query) use($request){
                $query->where('users.id', $request->user()->id);
        },
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
        ->map(function($product) use($request){
            $product->setAttribute('favourite', $product->favourite_product->isNotEmpty());
            return $product;
        });
             
        $products = ProductResource::collection($products);

        return response()->json([
            'products' => array_values($products->where('favourite', true)->toArray()),
        ]);
    }

    public function slider(){
        // https://bcknd.food2go.online/customer/home/slider
        $banners = $this->banner
        ->where('status', 1)
        ->with('category_banner')
        ->orderBy('order')
        ->get();

        return response()->json([
            'banners' => $banners
        ]);
    }

    public function favourite(Request $request, $id){
        // https://bcknd.food2go.online/customer/home/favourite/{id}
        // Keys
        // favourite
        $validator = Validator::make($request->all(), [
            'favourite' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $user = $this->user->where('id', auth()->user()->id)
        ->first();
        if ($request->favourite) {
            $user->favourite_product()->attach($id);
        }
        else{
            $user->favourite_product()->detach($id);
        }

        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    public function filter_product(Request $request){
        // https://bcknd.food2go.online/customer/home/filter_product
        // Keys
        // category_id, min_price, max_price, user_id
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation

        if ($request->user_id) {
            $user_id = $request->user_id;
            $products = $this->product
            ->where('category_id', $request->category_id)
            ->where('price', '>=', $request->min_price)
            ->where('price', '<=', $request->max_price)
            ->with(['favourite_product' => function($query) use($user_id){
                $query->where('users.id', $user_id);
            }, 'addons' => function($query) use($locale){
                $query->withLocale($locale);
            },'sub_category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'extra' => function($query) use($locale){
                $query->whereNull('option_id')
                ->withLocale($locale);
            }, 'variations' => function($query) use($locale){
                $query->withLocale($locale);
            }])
            ->withLocale($locale)
            ->where('status', 1)
            ->get();
            foreach ($products as $product) {
                if (count($product->favourite_product) > 0) {
                    $product->favourite = true;
                }
                else {
                    $product->favourite = false;
                }
                //get count of sales of product to detemine stock
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
            }
        }
        else{
            $products = $this->product
            ->where('category_id', $request->category_id)
            ->where('price', '>=', $request->min_price)
            ->where('price', '<=', $request->max_price)
            ->with(['addons' => function($query) use($locale){
                $query->withLocale($locale);
            },'sub_category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'extra' => function($query) use($locale){
                $query->whereNull('option_id')
                ->withLocale($locale);
            }, 'variations' => function($query) use($locale){
                $query->withLocale($locale);
            }])
            ->withLocale($locale)
            ->where('status', 1)
            ->get();
        }
        $products = ProductResource::collection($products);

        return response()->json([
            'products' => $products
        ]);
    }

    public function translation(){
        // https://bcknd.food2go.online/customer/home/translation
        $translation = $this->translations
        ->where('status', 1)
        ->get();

        return response()->json([
            'translation' => $translation
        ]);
    }

    public function menue(){
        $menue_images = $this->menue_image
        ->where('status', 1)
        ->get();

        return response()->json([
            'menue_images' => $menue_images
        ]);
    }
}
