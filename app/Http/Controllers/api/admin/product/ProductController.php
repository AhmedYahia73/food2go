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
use App\Models\TranslationTbl;

class ProductController extends Controller
{
    public function __construct(private Product $products,
    private Discount $discounts, private Tax $taxes, private ProductReview $reviews,
    private Translation $translations, private TranslationTbl $translation_tbl){}

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
        // https://bcknd.food2go.online/admin/product/item/{id}
        $product = $this->products
        ->with(['addons', 'excludes', 'extra', 'variations.options.extra', 'category', 'subCategory', 'discount', 'tax'])
        ->where('id', $id)
        ->first();//extra_id
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $product_names = [];
        $product_descriptions = [];
        $excludes = [];
        $extras = [];
        $variation = [];
        $variation_options = [];
        $variation_options_extra = [];
        $options = [];
        $extra_option = [];
        foreach ($translations as $key => $item) {
            $translation_file = $this->translation_tbl
            ->where('locale', $item->name)
            ->get();

            $product_names[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'product_name' => $translation_file->where('key', $product->name)
                ->first()->value ?? null
            ];
            $product_descriptions[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'product_description' => $translation_file->where('key', $product->description)
                ->first()->value ?? null
            ];
            foreach ($product->excludes as $exclude) {
                $excludes[$exclude->id]['names'][] = [
                    'tranlation_id' => $item->id,
                    'tranlation_name' => $item->name,
                    'exclude_name' => $translation_file->where('key', $exclude->name)
                    ->first()->value ?? null
                ];
            }
            foreach ($product->extra->whereNull('variation_id') as $key => $extra) {
                $extras[$extra->id]['names'][] = [
                    'tranlation_id' => $item->id,
                    'tranlation_name' => $item->name,
                    'extra_name' =>$translation_file->where('key', $extra->name)
                    ->first()->value ?? null
                ];
                $extras[$extra->id]['extra_price'] = $extra->price;
            }
            foreach ($product->variations as $key => $variation_item) {
                $variation[$variation_item->id]['names'][] = [
                    'tranlation_id' => $item->id,
                    'tranlation_name' => $item->name,
                    'name' => $translation_file->where('key', $variation_item->name)
                    ->first()->value ?? null
                ];
                $variation[$variation_item->id]['type'] = $variation_item->type;
                $variation[$variation_item->id]['min'] = $variation_item->min ?? 0;
                $variation[$variation_item->id]['max'] = $variation_item->max ?? 0;
                $variation[$variation_item->id]['required'] = $variation_item->required;
                foreach ($variation_item->options as $key => $option) {
                    $options[$variation_item->id][$option->id]['names'][] = [
                        'tranlation_id' => $item->id,
                        'tranlation_name' => $item->name,
                        'name' => $translation_file->where('key', $option->name)
                        ->first()->value ?? null
                    ];
                    $options[$variation_item->id][$option->id]['price'] = $option->price;
                    $options[$variation_item->id][$option->id]['status'] = $option->status;
                    $options[$variation_item->id][$option->id]['points'] = $option->points;
                    foreach ($option->extra as $key => $extra_item) {
                        $extra_options[$option->id][$extra_item->id]['extra_names'][] = [
                            'tranlation_id' => $item->id,
                            'tranlation_name' => $item->name,
                            'extra_name' => $translation_file->where('key', $extra_item->name)
                            ->first()->value ?? null
                        ];
                        $extra_options[$option->id][$extra_item->id]['extra_price'] = $extra_item->price;
                    }
                    if (isset($extra_options[$option->id])) {
                        $extra_option = array_values($extra_options[$option->id]);
                    }
                    else{
                        $extra_option = [];
                    }
                    $options[$variation_item->id][$option->id]['extra'] = $extra_option;
                }
                $variation[$variation_item->id]['options'] = array_values($options[$variation_item->id]);
            }
        }
        $product->product_names = $product_names;
        $product->product_descriptions = $product_descriptions;
        $product->exclude = array_values($excludes);
        $product->extras = array_values($extras);
        $product->variation = array_values($variation);

        return response()->json([
            'product' => $product
        ]);
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
