<?php

namespace App\Http\Controllers\api\admin\upsaling;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\UpsalingGroup;
use App\Models\Product;
use App\Models\TranslationTbl;
use App\Models\Translation;

class UpsalingController extends Controller
{
    public function __construct(private UpsalingGroup $upsaling,
    private Product $products, private Translation $translations, 
    private TranslationTbl $translation_tbl){}

    public function view(Request $request){
        $upsaling = $this->upsaling
        ->select("id", "name", "status")
        ->with(['products:id,name'])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "status" => $item->status,
                "products" => $item->products->select("id", "name"),
            ];
        });

        return response()->json([
            "upsaling" => $upsaling
        ]);
    }

    public function lists(Request $request){
        $products = $this->products
        ->select("id", "name")
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "products" => $products
        ]);
    }

    public function upsaling_item(Request $request, $id){
        $upsaling = $this->upsaling
        ->select("id", "name", "status")
        ->with(['products:id,name'])
        ->where("id", $id)
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        foreach ($translations as $key => $item) {
            $translation = $this->translation_tbl
            ->where('locale', $item->name)
            ->get();
            $names[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'product_name' => $translation->where('key', $upsaling->name)
                ->first()->value ?? null
            ];
        }

        return response()->json([
            "id" => $upsaling->id,
            "names" => $names,
            "status" => $upsaling->status,
            "products" => $upsaling?->products
            ?->select("id", "name"),
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'names' => 'required|array',
            'names.*.name' => 'required',
            'names.*.tranlation_id' => 'required',
            'names.*.tranlation_name' => 'required',
            'status' => 'required|boolean',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|exists:products,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $names = $request->names;
        $group_name = $names[0]['name'];
        $upsaling = $this->upsaling
        ->create([
            "name" => $group_name,
            "status" => $request->status,
        ]);
        $upsaling->products()->attach($request->product_ids);
        foreach ($names as $item) {
            $upsaling->translations()->create([
                'locale' => $item['tranlation_name'],
                'key' => $group_name,
                'value' => $item['name']
            ]);
        }

        return response()->json([
            "success" => "You add upsaling"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'names' => 'required|array',
            'names.*.name' => 'required',
            'names.*.tranlation_id' => 'required',
            'names.*.tranlation_name' => 'required',
            'status' => 'required|boolean',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|exists:products,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $names = $request->names;
        $group_name = $names[0]['name'];
        $upsaling = $this->upsaling
        ->where("id", $id)
        ->first();
        $upsaling->update([
            "name" => $group_name ?? $upsaling->name,
            "status" => $request->status ?? $upsaling->status,
        ]);
        $upsaling->products()->sync($request->product_ids);
        $upsaling->translations()->delete();
        foreach ($names as $item) {
            $upsaling->translations()->create([
                'locale' => $item['tranlation_name'],
                'key' => $group_name,
                'value' => $item['name']
            ]);
        }

        return response()->json([
            "success" => "You update upsaling"
        ]);
    }

    public function delete(Request $request, $id){
        $upsaling = $this->upsaling
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete upsaling"
        ]);
    }
}
