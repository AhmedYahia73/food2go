<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;

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

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user,
    private Product $product, private Banner $banner, private Setting $settings,
    private Translation $translations, private BranchOff $branch_off,
    private Address $address, private ScheduleSlot $schedule_list,
    private MainData $main_data, private Policy $policies,
    private MenueImage $menue_image){}

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
            }, 'discount', 'extra',
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
            ->map(function($product) use($category_off, $product_off, $option_off){
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
                // return !$category_off->contains($item->id);
                // $category_off, $product_off, $option_off
                if ($category_off->contains($product->category_id) || 
                $category_off->contains($product->sub_category_id)
                || $product_off->contains($product->id)) {
                    return null;
                }
                $product->variations = $product->variations->map(function ($variation) use ($option_off) {
                    $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                    return $variation;
                });
                return $product;
            })->filter(); 
        }
        else{
            $products = $this->product
            ->with(['addons' => function($query) use($locale){
                $query->withLocale($locale);
            },'category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            },'sub_category_addons' => function($query) use($locale){
                $query->withLocale($locale);
            },'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'discount', 'extra', 
             
            'variations' => function($query) use($locale){
                $query->withLocale($locale)
                ->with(['options']);
            }, 'sales_count', 'tax'])
            ->withLocale($locale)
            ->where('item_type', '!=', 'offline')
            ->where('status', 1)
            ->get()
            ->map(function($product) use($category_off, $product_off, $option_off){ 
                if ($category_off->contains($product->category_id) || 
                $category_off->contains($product->sub_category_id)
                || $product_off->contains($product->id)) {
                    return null;
                }
                $product->variations = $product->variations->map(function ($variation) use ($option_off) {
                    $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
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
            ->get()
            ->map(function($item) use($category_off, $product_off, $option_off){ 
                if (count($item->favourite_product) > 0) {
                    $item->favourite = true;
                }
                else {
                    $item->favourite = false;
                }
                if ($category_off->contains($item->category_id) || 
                $category_off->contains($item->sub_category_id)
                || $product_off->contains($item->id)) {
                    return null;
                }
                $item->variations = $item->variations->map(function ($variation) use ($option_off) {
                    $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
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
            ->map(function($product) use($category_off, $product_off, $option_off){ 
        
                if ($category_off->contains($product->category_id) || 
                $category_off->contains($product->sub_category_id)
                || $product_off->contains($product->id)) {
                    return null;
                }
                $product->variations = $product->variations->map(function ($variation) use ($option_off) {
                    $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
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
        ->get();
             
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
