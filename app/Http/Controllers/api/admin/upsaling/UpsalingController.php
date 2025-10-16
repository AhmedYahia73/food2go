<?php

namespace App\Http\Controllers\api\admin\upsaling;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\UpsalingGroup;
use App\Models\Product;

class UpsalingController extends Controller
{
    public function __construct(private UpsalingGroup $upsaling,
    private Product $products){}

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
                "id" => $id,
                "name" => $name,
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

        return response()->json([
            "id" => $upsaling->id,
            "name" => $upsaling->name,
            "status" => $upsaling->status,
            "products" => $upsaling?->products
            ?->select("id", "name"),
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|exists:products,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $upsalingRequest = $validator->validated();
        $upsaling = $this->upsaling
        ->create([
            "name" => $request->name,
            "status" => $request->status,
        ]);
        $upsaling->products()->attach($request->product_ids);

        return response()->json([
            "success" => "You add upsaling"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes',
            'status' => 'sometimes|boolean',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|exists:products,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $upsaling = $this->upsaling
        ->where("id", $id)
        ->first();
        $upsaling->update([
            "name" => $request->name ?? $upsaling->name,
            "status" => $request->status ?? $upsaling->status,
        ]);
        $upsaling->products()->sync($request->product_ids);

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
