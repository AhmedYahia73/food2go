<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Banner;

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user,
    private Product $product, private Banner $banner){}

    public function products(){
        // https://backend.food2go.pro/customer/home
        $categories = $this->categories
        ->with(['sub_categories', 'addons'])
        ->where('category_id', null)
        ->get();
        $products = $this->product
        ->with(['favourite_product', 'addons', 'excludes', 'extra', 'discount', 
        'variations.options.extra.parent_extra', 'sales_count', 'tax'])
        ->where('item_type', '!=', 'offline')
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
        $discounts = $this->product
        ->with('discount')
        ->whereHas('discount')
        ->get();

        return response()->json([
            'categories' => $categories,
            'products' => $products,
            'discounts' => $discounts
        ]);
    }

    public function slider(){
        // https://backend.food2go.pro/customer/home/slider
        $banners = $this->banner
        ->with('category_banner')
        ->orderBy('order')
        ->get();

        return response()->json([
            'banners' => $banners
        ]);
    }

    public function favourite(Request $request, $id){
        // https://backend.food2go.pro/customer/home/favourite/{id}
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
        // https://backend.food2go.pro/customer/home/filter_product
        // Keys
        // category_id, min_price, max_price
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

        $products = $this->product
        ->where('category_id', $request->category_id)
        ->where('price', '>=', $request->min_price)
        ->where('price', '<=', $request->max_price)
        ->with(['favourite_product', 'addons', 'excludes', 'extra', 'variations'])
        ->get();

        return response()->json([
            'products' => $products
        ]);
    }
}
