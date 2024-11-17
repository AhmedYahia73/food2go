<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use App\Models\Product;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\ProductReview;
use App\Models\Translation;

class ProductController extends Controller
{
    public function __construct(private Product $products,
    private Discount $discounts, private Tax $taxes, private ProductReview $reviews
    ,private Translation $translations){}

    public function view(){
        // https://bcknd.food2go.online/admin/product
        $products = $this->products
        ->with(['addons', 'excludes', 'extra', 'variations.options.extra',
        'category', 'subCategory', 'discount', 'tax'])
        ->get();//extra_id
        $discounts = $this->discounts
        ->get();
        $taxes = $this->taxes
        ->get();

        return response()->json([
            'products' => $products,
            'discounts' => $discounts,
            'taxes' => $taxes,
        ]);
    }

    public function product($id){
        $product = $this->product
        ->with(['addons', 'excludes', 'extra', 'variations.options.extra'])
        ->first();//extra_id
        $translations = $this->translations
        ->get();
        $product_names = [];
        $product_descriptions = [];
        foreach ($translations as $item) {
            $filePath = base_path("lang/{$item->name}/messages.php");
            if (File::exists($filePath)) {
                $translation_file = require $filePath;
                $product_names[] = [
                    'tranlation_id' => $item->id,
                    'tranlation_name' => $item->name,
                    'product_name' => $translation_file[$product->name] ?? null
                ];
                $product_descriptions[] = [
                    'tranlation_id' => $item->id,
                    'tranlation_name' => $item->name,
                    'product_description' => $translation_file[$product->description] ?? null
                ];
            }
        }
        $product->product_names = $product_names;
        $product->product_descriptions = $product_descriptions;
    }

    public function reviews(){
        // https://bcknd.food2go.online/admin/product/reviews
        $reviews = $this->reviews
        ->with(['product', 'customer'])
        ->get();

        return response()->json([
            'reviews' => $reviews
        ]);
    }
}
