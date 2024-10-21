<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;
use App\Models\User;

class HomeController extends Controller
{
    public function __construct(private Category $categories, private User $user){}

    public function products(){
        // https://backend.food2go.pro/customer/home
        $categories = $this->categories
        ->with('products.favourite_product')
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

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function favourite(Request $request, $id){
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
}
