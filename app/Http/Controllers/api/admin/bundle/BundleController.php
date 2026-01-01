<?php

namespace App\Http\Controllers\api\admin\bundle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Bundle;
use App\Models\BundleOption;
use App\Models\BundleVariation;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\Translation;
use App\Models\Category;
use App\Models\Product;
use App\Models\TranslationTbl;

class BundleController extends Controller
{
    public function __construct(private Bundle $bundles, private Product $product,
    private Translation $translations, private TranslationTbl $translation_tbl){}
    use image;

    public function view(Request $request){
        $bundles = $this->bundles  
        ->with("products.variations", "products.variations.options.bundle_options", 
        "bundle_variations", 'discount', 'tax')
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "description" => $item->description,
                "image" => $item->image_link,
                'discount_id' => $item->discount_id,
                'tax_id' => $item->tax_id,
                'price' => $item->price,
                'status' => $item->status,
                'points' => $item->points,
                'discount' => $item?->discount?->name,
                'tax' => $item?->tax?->name, 
            ];
        });

        return response()->json([
            "bundles" => $bundles,
        ]);
    }

    public function lists(){
        $discounts = Discount::
        get();
        $taxes = Tax::
        get();
        $categories = Category::
        get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $products = Product::
        with("variations.options")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "variations" => $item?->variations?->map(function($element){
                    return [
                        "id" => $element->id,
                        'name' => $element->name,
                        'type' => $element->type,
                        'min' => $element->min,
                        'max' => $element->max,
                        'required' => $element->required,
                        'options' => $element->options
                        ->where("status", 1)
                        ->map(function($value){
                            return [
                                'name' => $value->name,
                                'price' => $value->price,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json([
            "discounts" => $discounts,
            "taxes" => $taxes,
            "products" => $products,
            "categories" => $categories,
        ]);
    }

    public function bundle_item(Request $request, $id){
        $bundle = $this->bundles
        ->with("products.variations", "products.variations.options.bundle_options", 
        "bundle_variations", 'discount', 'tax')
        ->where("id", $id)
        ->first();
        
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $bundle_names = [];
        foreach ($translations as $item) {
            $name = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $bundle->name)
            ->first();
           $bundle_names[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'name' => $name->value ?? null,
           ]; 
        }
        $bundle_decriptions = [];
        foreach ($translations as $item) {
            $description = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $bundle->description)
            ->first();
           $bundle_decriptions[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'name' => $description->value ?? null,
           ]; 
        }
        if(empty($bundle)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        } 

        return response()->json([
            "id" => $bundle->id,
            "bundle_names" => $bundle_names, 
            "bundle_decriptions" => $bundle_decriptions, 
            "image" => $bundle->image_link,
            'discount_id' => $bundle->discount_id,
            'tax_id' => $bundle->tax_id,
            'price' => $bundle->price,
            'status' => $bundle->status,
            'points' => $bundle->points,
            'discount' => $bundle?->discount?->name,
            'tax' => $bundle?->tax?->name,
            'products' => $bundle?->products
            ?->map(function($element) use($bundle){
                return [
                    "id" => $element->id,
                    "name" => $element->name,
                    "variations" => $element->variations
                    ->map(function($value) use($element, $bundle){
                        return [
                            "id" => $value->id,
                            "variation_selected" => $bundle->bundle_variations
                            ->where("product_id", $element->id)
                            ->first()
                            ? 1 : 0,
                            "variation" => $value?->name,
                            "type" => $value?->type,
                            "min" => $value?->min,
                            "max" => $value?->max,
                            "required" => $value?->required,
                            "options" => $value?->options
                            ->map(function($new_item) use($bundle){
                                return [
                                    "id" => $new_item->id,
                                    "name" => $new_item->name,
                                    "price" => $new_item->price,
                                    "selected" => $new_item->bundle_options
                                    ->where("bundle_id", $bundle->id)
                                    ->first()
                                    ? 1 : 0,
                                ];
                            }),
                        ];
                    })
                ];
            })
            
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 

        $bundle = $this->bundles
        ->where("id", $id)
        ->update([
            "status" => $request->status
        ]);

        return response()->json([
            "success" => $request->status ? "active" : "banned"
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'bundle_names' => ['required', "array"],
            'bundle_names.*.tranlation_id' => ['required'],
            'bundle_names.*.tranlation_name' => ['required', "exists:translations,name"],
            'bundle_names.*.name' => ['required'],
            'bundle_descriptions' => ["array"],
            'bundle_descriptions.*.tranlation_id' => ['required'],
            'bundle_descriptions.*.tranlation_name' => ['required', "exists:translations,name"],
            'bundle_descriptions.*.description' => ['required'],
            'image' => ['required'],
            'discount_id' => ['sometimes', 'exists:discounts,id'],
            'tax_id' => ['sometimes', 'exists:taxes,id'],
            'price' => ['required', 'numeric'],
            'status' => ['required', 'boolean'],
            'points' => ['required', 'numeric'],
            'products' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.variation' => ['array'],
            'products.*.variation.*.id' => ['required', 'exists:variation_products,id'],
            'products.*.variation.*.options' => ['required', 'array'],
            'products.*.variation.*.options.*' => ['required', 'exists:option_products,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $bundle_names = $request->bundle_names[0];
        $bundle_descriptions = $request->bundle_descriptions ? $request->bundle_descriptions[0] : [];
        $imag_path = $this->upload($request, 'image', 'admin/bundle');
        $bundle = Bundle::create([
            'name' => $bundle_names['name'],
            'description' => isset($bundle_descriptions['description']) ? $bundle_descriptions['description'] : null,
            'image' => $imag_path,
            'discount_id' => $request->discount_id,
            'tax_id' => $request->tax_id,
            'price' => $request->price,
            'status' => $request->status,
            'points' => $request->points,
        ]);
        $bundle->products()->attach(array_column($request->products, "id"));
        foreach ($request->products as $item) {
            if(isset($item['variation'])){
                foreach ($item['variation'] as $element) {
                    $variation_bundle = BundleVariation::create([
                        'bundle_id' => $bundle->id,
                        'variation_id' => $element['id'],
                        'product_id' => $item['id'],
                    ]);
                    foreach ($element['options'] as $key => $value) {
                        BundleOption::create([
                            'bundle_id' => $bundle->id,
                            'variation_id' => $element['id'],
                            'option_id' => $value, 
                        ]);
                    }
                }
            }
        }
        foreach ($request->bundle_names as $item) {
            if (!empty($item['name'])) {
                $bundle->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $bundle_names['name'],
                    'value' => $item['name']
                ]);
            }
        }
        if($request->bundle_descriptions){ 
            foreach ($request->bundle_descriptions as $item) {
                if (!empty($item['bundle_descriptions'])) {
                    $bundle->translations()->create([
                        'locale' => $item['tranlation_name'],
                        'key' => $bundle_descriptions['description'],
                        'value' => $item['description']
                    ]);
                }
            }
        }

        return response()->json([
            "success" => "You add bundle success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
          
            'bundle_names' => ['required', "array"],
            'bundle_names.*.tranlation_id' => ['required'],
            'bundle_names.*.tranlation_name' => ['required', "exists:translations,name"],
            'bundle_names.*.name' => ['required'],
            'bundle_descriptions' => ["array"],
            'bundle_descriptions.*.tranlation_id' => ['required'],
            'bundle_descriptions.*.tranlation_name' => ['required', "exists:translations,name"],
            'bundle_descriptions.*.description' => ['required'],
            'image' => ['required'],
            'discount_id' => ['sometimes', 'exists:discounts,id'],
            'tax_id' => ['sometimes', 'exists:taxes,id'],
            'price' => ['required', 'numeric'],
            'status' => ['required', 'boolean'],
            'points' => ['required', 'numeric'],
            'products' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.variation' => ['array'],
            'products.*.variation.*.id' => ['required', 'exists:variation_products,id'],
            'products.*.variation.*.options' => ['required', 'array'],
            'products.*.variation.*.options.*' => ['required', 'exists:option_products,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $bundle_names = $request->bundle_names[0];
        $bundle_descriptions = $request->bundle_descriptions ? $request->bundle_descriptions[0] : [];
        $bundle = Bundle::
        where("id", $id)
        ->first();
        if(empty($bundle)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $imag_path = null;
        if($request->image){
            $imag_path = $this->upload($request, 'image', 'admin/bundle');
            $this->deleteImage($bundle->image);
        }
        $bundle->update([
            'name' => $bundle_names['name'],
            'description' => isset($bundle_descriptions['description']) ? $bundle_descriptions['description'] : null,
            'image' => $request->image ? $imag_path : $bundle->image,
            'discount_id' => $request->discount_id,
            'tax_id' => $request->tax_id,
            'price' => $request->price,
            'status' => $request->status,
            'points' => $request->points,
        ]);
        $bundle->products()->sync(array_column($request->products, "id"));
        foreach ($request->products as $item) {
            if(isset($item['variation'])){
                BundleVariation::
                where("bundle_id", $bundle->id)
                ->where("product_id", $item['id'])
                ->delete();
                foreach ($item['variation'] as $element) {
                    $variation_bundle = BundleVariation::create([
                        'bundle_id' => $bundle->id,
                        'variation_id' => $element['id'],
                        'product_id' => $item['id'],
                    ]);
                    BundleOption::
                    where("bundle_id", $bundle->id)
                    ->where("variation_id", $element['id'])
                    ->delete();
                    foreach ($element['options'] as $key => $value) {
                        BundleOption::create([
                            'bundle_id' => $bundle->id,
                            'variation_id' => $element['id'],
                            'option_id' => $value, 
                        ]);
                    }
                }
            }
        }
        $bundle->translations()->delete();

        foreach ($request->bundle_names as $item) {
            if (!empty($item['name'])) {
                $bundle->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $bundle_names['name'],
                    'value' => $item['name']
                ]);
            }
        }
        if($request->bundle_descriptions){ 
            foreach ($request->bundle_descriptions as $item) {
                if (!empty($item['bundle_descriptions'])) {
                    $bundle->translations()->create([
                        'locale' => $item['tranlation_name'],
                        'key' => $bundle_descriptions['description'],
                        'value' => $item['description']
                    ]);
                }
            }
        }

        return response()->json([
            "success" => "You update bundle success"
        ]);
    }

    public function delete(Request $request, $id){
        $bundle = Bundle::
        where("id", $id)
        ->first();
        if(empty($bundle)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $this->deleteImage($bundle->image);
        $bundle->delete();
        
        return response()->json([
            "success" => "You delete bundle success"
        ]);
    }
}
