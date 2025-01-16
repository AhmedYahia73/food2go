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

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user,
    private Product $product, private Banner $banner, private Setting $settings,
    private Translation $translations){}

    public function products(Request $request){
        // https://bcknd.food2go.online/customer/home
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $categories = $this->categories
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->withLocale($locale)
        ->where('category_id', null)
        ->get();
        if ($request->user_id) {
            $user_id = $request->user_id;
            $products = $this->product
            ->with(['favourite_product' => function($query) use($user_id){
                $query->where('users.id', $user_id);
            }, 'addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'extra' => function($query) use($locale){
                $query->whereNull('option_id')
                ->withLocale($locale);
            }, 'discount', 
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
            ->with([ 'addons' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'excludes' => function($query) use($locale){
                $query->withLocale($locale);
            }, 'extra' => function($query) use($locale){
                $query->whereNull('option_id')
                ->withLocale($locale);
            }, 'discount', 
             
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
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $categories = $this->categories
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->withLocale($locale)
        ->where('category_id', null)
        ->get();
    
        $products = $this->product
        ->with(['addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'excludes' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'extra' => function($query) use($locale){
            $query->whereNull('option_id')
            ->withLocale($locale);
        }, 'discount', 
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
        }, 'discount', 
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
                'error' => $validator->errors(),
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
                'error' => $validator->errors(),
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
}
