<?php

namespace App\Http\Controllers\api\customer\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;

class HomeController extends Controller
{
    public function __construct(private Category $categories){}

    public function products(){
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
}
