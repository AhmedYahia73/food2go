<?php

namespace App\Http\Controllers\api\admin\bundle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Bundle;
use App\Models\Discount;
use App\Models\Tax;

class BundleController extends Controller
{
    public function __construct(private Bundle $bundles){}
    use image;

    public function view(Request $request){
        $bundles = $this->bundles
        ->with("products", 'discount', 'tax')
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
                'products' => $item?->products
                ?->map(function($element){
                    return [
                        "id" => $element->id,
                        "name" => $element->name,
                    ];
                })
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

        return response()->json([
            "discounts" => $discounts,
            "taxes" => $taxes,
        ]);
    }

    public function bundle_item(Request $request, $id){
        $bundle = $this->bundles
        ->with("products", 'discount', 'tax')
        ->where("id", $id)
        ->first();
        if(empty($bundle)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        } 

        return response()->json([
            "id" => $bundle->id,
            "name" => $bundle->name,
            "description" => $bundle->description,
            "image" => $bundle->image_link,
            'discount_id' => $bundle->discount_id,
            'tax_id' => $bundle->tax_id,
            'price' => $bundle->price,
            'status' => $bundle->status,
            'points' => $bundle->points,
            'discount' => $bundle?->discount?->name,
            'tax' => $bundle?->tax?->name,
            'products' => $bundle?->products
            ?->map(function($element){
                return [
                    "id" => $element->id,
                    "name" => $element->name,
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
            'products.*' => ['required', 'exists:products,id'],
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
        $bundle->products()->attach($request->products);
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
            'products.*' => ['required', 'exists:products,id'],
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
        $bundle->products()->sync($request->products);
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
