<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\AddonResource;
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
use App\Models\PaymentMethod; 
use App\Models\Addon;

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user,
    private Product $product, private Banner $banner, private Setting $settings,
    private Translation $translations, private BranchOff $branch_off,
    private Address $address, private ScheduleSlot $schedule_list,
    private MainData $main_data, private Policy $policies,
    private MenueImage $menue_image, private SmsBalance $sms_balance,
    private Addon $addons, private PaymentMethod $payment_method){}

    public function mainData(){
        // https://bcknd.food2go.online/customer/home/main_data
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
                $product->favourites = $product->favourite_product->isNotEmpty();

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

    public function product_item(Request $request, $id){
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
        $option_off = $branch_off->pluck('option_id')->filter();

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
            ->where('id', $id)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1)
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {
                $product->favourite = $product->favourite_product->isNotEmpty();
                $product->favourites = $product->favourite_product->isNotEmpty();

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
                        'options' => fn($oq) => $oq->with(['option_pricing'])
                        ->withLocale($locale) // تأكد دي مطلوبة
                    ])->withLocale($locale),
                ])
                ->withLocale($locale)
                ->where('item_type', '!=', 'offline')
                ->where('status', 1)
                ->where('id', $id)
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
        }
        if($products->count() == 0){
            return response()->json([
                'errors' => 'id is wrong'
            ], 400);
        }
        $product = ProductResource::collection($products);
        $product = $product[0];
        $product->tax;
        $cate_addons = $this->addons
		->with('translations', 'tax')
        ->withLocale($locale)
        ->whereHas('categories', function($query) use($product){
            $query->where('categories.id', $product->category_id)
            ->orWhere('categories.id', $product->sub_category_id);
        })
        ->get();
        $cate_addons = AddonResource::collection($cate_addons);
        $addons = collect($product->addons)
        ->merge($cate_addons)
        ->values()
		->map(function($item){
            $locale = app()->getLocale(); // Use the application's current locale
            if ($item?->taxes?->setting && $item?->taxes?->setting == 'included') {
                $price =  empty($item->tax) ? $item->price: 
                ($item->tax->type == 'value' ? $item->price + $item->tax->amount : $item->price + $item->tax->amount * $item->price / 100);

                $tax = $price;
                $discount = $price;
                $addon_arr = [
                    'id' => $item->id,
                    'name' => $item->translations->where('key', $item->name)->first()?->value ?? $item->name,
                    'price' => $price,
                    'price_after_tax' => $tax,
                    'price_after_discount' => $discount,
                    'discount_val' => $price - $discount,
                    'tax_val' => $tax - $price,
                    'tax_id' => $item->tax_id,
                    'quantity_add' => $item->quantity_add,
                    'tax' => $item->relationLoaded('tax') ? $item->tax : null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];    
                if ($item->discount && !empty($item->discount) && $item->discount->type == 'precentage') {
                    $discount = $price - $item->discount->amount * $price / 100;
                    $addon_arr['price_after_discount'] = $discount;
                }   
            }
            else {
                $price = $item->price;
                
                if (!empty($item->tax)) {
                    if ($item->tax->type == 'precentage') {
                        $tax = $price + $item->tax->amount * $price / 100;
                    } else {
                        $tax = $price + $item->tax->amount;
                    }
                }
                else{
                    $tax = $price;
                }
                $addon_arr = [
                    'id' => $item->id,
                    'name' => $item->translations->where('key', $item->name)->first()?->value ?? $item->name,
                    'price' => $price,
                    'price_after_tax' => $tax,
                    'discount_val' => 0,
                    'tax_val' => $tax - $price,
                    'tax_id' => $item->tax_id,
                    'quantity_add' => $item->quantity_add,
                    'tax' => $item->tax,
                ];
                if ($item->discount && !empty($item->discount) && $item->discount->type == 'precentage') {
                    $discount = $price - $item->discount->amount * $price / 100;
                    $addon_arr['price_after_discount'] = $discount;
                    $addon_arr['discount_val'] = $price - $discount;
                }

            }
            return $addon_arr;
		});
 
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'sub_category_id' => $product->sub_category_id,
            'description' => $product->description, 
            'price' => $product->price, 
            'price_after_discount' => $product->toArray(request())['price_after_discount'], 
            'price_after_tax' => $product->toArray(request())['price_after_tax'], 
            'discount_val' => $product->toArray(request())['discount_val'], 
            'tax_val' => $product->toArray(request())['tax_val'], 
            'recommended' => $product->recommended, 
            'image_link' => $product->image_link, 
            'allExtras' => $product->toArray(request())['allExtras'],  
            'addons' => $addons, 
            'variations' => $product->variations, 
            'excludes' => $product->excludes->select('id', 'name'),
            'tax_obj' => $product->toArray(request())['tax_obj'],
            'favourite' => $product->favourite,
        ]);
    }

    public function payment_methods(Request $request){
        $payment_methods = $this->payment_method
        ->select('id', 'name', 'description', 'logo')
        ->orderBy('order')
        ->get();
        $schedules = $this->schedule_list
        ->select('id', 'name')
        ->get();

        return response()->json([
            'payment_methods' => $payment_methods,
            'schedules' => $schedules,
        ]);
    }
    public function categories(Request $request){
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
        $category_off = $branch_off->pluck('category_id')->filter();
        $categories = $this->categories
        ->orderBy('priority')
        ->where('status', 1)
        ->with(['sub_categories' => function($query) use($locale, $category_off){
            $query->where('status', 1)
            ->whereNotIn('id', $category_off->toArray())
            ->withLocale($locale);
        }])
        ->withLocale($locale) 
        ->where('category_id', null)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        })
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->translations->where('key', $item->name)->first()?->value ?? $item->name,
                'image_link' => $item->image_link,
                'banner_link' => $item->banner_link,
                'sub_categories' => $item->sub_categories
                ->map(function($element){
                    return [
                        'id' => $element->id,
                        'name' => $element->translations->where('key', $element->name)->first()?->value ?? $element->name,
                        'image_link' => $element->image_link,
                        'banner_link' => $element->banner_link,
                    ];
                }),
            ];
        });

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function products_in_category(Request $request, $id){
        
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
    
        if ($request->user_id) {
            $user_id = $request->user_id;
            $products = $this->product
            ->with([ 
                'favourite_product' => fn($q) => $q->where('users.id', $user_id),
            ])
            ->withLocale($locale)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1)
            ->where(function($query) use($id){
                $query->where('sub_category_id', $id)
                ->orWhere('category_id', $id);
            })
            // ->whereNotIn('sub_category_id', $category_off)
            ->whereNotIn('products.id', $product_off)
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {
                $product->favourites = $product->favourite_product->isNotEmpty();
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
                        'category_id' => $product->category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'recommended' => $product->recommended,
                        'image_link' => $product->image_link,
                        'discount' => $price - $discount,
                        'tax' => $tax - $price,
                        'favourite' => is_bool($product->favourites) ? $product->favourites : false,
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
                        'category_id' => $product->category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'recommended' => $product->recommended,
                        'image_link' => $product->image_link,
                        'discount' => $price - $discount,
                        'tax' => $tax - $price,
                        'favourite' => is_bool($product->favourites) ? $product->favourites : false,
                    ];
                } 
            });

        }
        else{
            $products = $this->product 
            ->withLocale($locale)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1) 
            ->where(function($query) use($id){
                $query->where('sub_category_id', $id)
                ->orWhere('category_id', $id);
            })
            // ->whereNotIn('sub_category_id', $category_off)
            ->whereNotIn('products.id', $product_off)
            ->get()
            ->map(function ($product) use ($option_off, $branch_id) {
                $product->favourite = $product->favourite_product->isNotEmpty();
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
                        'recommended' => $product->recommended,
                        'image_link' => $product->image_link,
                        'category_id' => $product->category_id,
                        'sub_category_id' => $product->sub_category_id,
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
                        'category_id' => $product->category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'recommended' => $product->recommended,
                        'image_link' => $product->image_link,
                        'discount' => $price - $discount,
                        'tax' => $tax - $price,
                    ];
                } 
            });
        }

        return response()->json([
            'products' => $products,
            'tax' => $tax,
        ]);
    }

    public function recommandation_product(Request $request){
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
        ->withLocale($locale)
        ->where('item_type', '!=', 'offline')
        ->where('recommended', 1)
        ->where('status', 1) 
        // ->whereNotIn('sub_category_id', $category_off)
        ->where(function($query) use($category_off){
            $query->whereNotIn('sub_category_id', $category_off)
            ->orWhereNotIn('category_id', $category_off);
        })
        ->whereNotIn('products.id', $product_off)
        ->get()
        ->map(function ($product) use ($option_off, $branch_id) {
            $product->favourite = $product->favourite_product->isNotEmpty();
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
            'recommended_products' => $products
        ]);
    }

    public function discount_product(Request $request){
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
        ->withLocale($locale)
        ->where('item_type', '!=', 'offline')
        ->whereHas('discount')
        ->where('status', 1) 
        // ->whereNotIn('sub_category_id', $category_off)
        ->where(function($query) use($category_off){
            $query->whereNotIn('sub_category_id', $category_off)
            ->orWhereNotIn('category_id', $category_off);
        })
        ->whereNotIn('products.id', $product_off)
        ->get()
        ->map(function ($product) use ($option_off, $branch_id) {
            $product->favourite = $product->favourite_product->isNotEmpty();
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
                    $variation->options = $variation->options
                        ->where('status', 1)
                        ->values()->reject(fn($option) => $option_off->contains($option->id));
                    $variation->options = $variation->options
                        ->where('status', 1)
                        ->values()->map(function($element) use($branch_id){
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
                    $variation->options = $variation->options
                        ->where('status', 1)
                        ->values()->reject(fn($option) => $option_off->contains($option->id));
                    $variation->options = $variation->options
                        ->where('status', 1)
                        ->values()->map(function($element) use($branch_id){
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
