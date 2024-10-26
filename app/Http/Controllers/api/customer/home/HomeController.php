<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user,
    private Product $product){}

    public function products(){
        // https://backend.food2go.pro/customer/home
        $categories = $this->categories
        ->with(['category_products' => function($query){
            $query
            ->where('item_type', '!=', 'offline')
            ->with(['favourite_product', 'addons', 'excludes', 'extra', 'variations', 'discount']);
        }, 'addons'])
        ->where('category_id', null)
        ->get();
        $sub_categories = $this->categories
        ->with(['sub_categories.products' => function($query){
            $query
            ->where('item_type', '!=', 'offline')
            ->with(['favourite_product', 'addons', 'excludes', 'extra', 'variations', 'discount']);
        }, 'addons'])
        ->where('category_id', null)
        ->get();
        foreach ($categories as $category) {
            foreach ($category->products as $product) {
                if (count($product->favourite_product) > 0) {
                    $product->favourite = true;
                }
                else {
                    $product->favourite = false;
                }
            }
        }
        $discounts = $this->product
        ->with('discount')
        ->whereHas('discount')
        ->get();

        return response()->json([
            'categories' => $categories,
            'sub_categories' => $sub_categories,
            'discounts' => $discounts
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
