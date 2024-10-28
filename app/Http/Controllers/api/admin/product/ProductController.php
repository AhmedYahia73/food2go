<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\ProductReview;

class ProductController extends Controller
{
    public function __construct(private Product $products, private Category $categories,
    private Discount $discounts, private Tax $taxes, private ProductReview $reviews){}

    public function view(){
        // https://backend.food2go.pro/admin/product
        $products = $this->products
        ->with(['addons', 'excludes', 'extra', 'variations.options.extra.parent_extra'])
        ->get();//extra_id
        $categories = $this->categories
        ->get();
        $discounts = $this->discounts
        ->get();
        $taxes = $this->taxes
        ->get();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'discounts' => $discounts,
            'taxes' => $taxes,
        ]);
    }

    public function reviews(){
        // https://backend.food2go.pro/admin/product/reviews
        $reviews = $this->reviews
        ->with(['product', 'customer'])
        ->get();

        return response()->json([
            'reviews' => $reviews
        ]);
    }
}
