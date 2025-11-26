<?php

namespace App\Http\Controllers\api\admin\group_price;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\GroupProduct;

class GroupProductController extends Controller
{
    public function __construct(private GroupProduct $group_product){}

    protected $groupRequest = [
        'name',  
        'increase_precentage', 
        'decrease_precentage', 
        'due',
        'status',
    ];

    public function view(Request $request){
        $group_products = $this->group_product
        ->select("id", "name", "increase_precentage", "decrease_precentage", "module", "status", "due")
        ->get();
        $modules = [
            "take_away",
            "delivery",
            "dine_in",
        ];

        return response()->json([
            "group_products" => $group_products,
            "modules" => $modules,
        ]);
    }
    
    public function group_item(Request $request, $id){
        $group_product = $this->group_product
        ->select("id", "name", "increase_precentage", "decrease_precentage", "module", "status", "due")
        ->where("id", $id)
        ->first();

        return response()->json([
            "group_product" => $group_product
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->group_product
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
            'name' => ['required'], 
            'module' => ['required', "array"], 
            'module.*' => ['required', "in:take_away,delivery,dine_in"], 
            'increase_precentage' => ['required', 'numeric'], 
            'decrease_precentage' => ['required', 'numeric'], 
            'due' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $groupRequest = $request->only($this->groupRequest);
        $groupRequest['module'] = json_encode($request->module);
        $this->group_product
        ->create($groupRequest);

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'], 
            'module' => ['required', "array"], 
            'module.*' => ['required', "in:take_away,delivery,dine_in"], 
            'increase_precentage' => ['required', 'numeric'], 
            'decrease_precentage' => ['required', 'numeric'], 
            'due' => ['required', 'boolean'],
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $groupRequest = $request->only($this->groupRequest);
        $groupRequest['module'] = json_encode($request->module);
        $this->group_product
        ->where("id", $id)
        ->update($groupRequest);

        return response()->json([
            "success" => "You update data success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->group_product
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
