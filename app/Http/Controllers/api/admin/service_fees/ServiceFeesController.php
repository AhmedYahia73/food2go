<?php

namespace App\Http\Controllers\api\admin\service_fees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ServiceFees; 
use App\Models\Branch; 

class ServiceFeesController extends Controller
{
    public function __construct(private ServiceFees $service_fees,
    private Branch $branches){}

    public function view(Request $request){
        $service_fees = $this->service_fees
        ->with("branches:id,name")
        ->get();

        return response()->json([
            "service_fees" => $service_fees, 
        ]);
    }

    public function lists(Request $request){
        $branches = $this->branches
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $types = ['precentage','value'];
        $modules = ["pos", "online"];
        $online_types = ["all", "app", "web"];

        return response()->json([
            "branches" => $branches, 
            "types" => $types, 
            "modules" => $modules, 
            "online_types" => $online_types, 
        ]);
    }

    public function service_fees_item(Request $request, $id){
        $service_fees = $this->service_fees
        ->with("branches:id,name")
        ->where("id", $id)
        ->first();

        return response()->json([
            "service_fees" => $service_fees, 
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

        $service_fees = $this->service_fees 
        ->where("id", $id)
        ->update([
            "status" => $request->status
        ]); 

        return response()->json([
            "success" => "You update status success", 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:precentage,value'],
            'amount' => ['required', 'numeric'],
            'module' => ['required', 'in:pos,online'],
            'online_type' => ['sometimes', 'in:all,app,web'],
            'branches' => ['required', 'array'],
            'branches.*' => ['required', 'exists:branches,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $serviceFeesRequest = $validator->validated();
        $service_fees = $this->service_fees
        ->create($serviceFeesRequest);
        $service_fees->branches()->attach($request->branches);

        return response()->json([
            "success" => "You add service fees success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:precentage,value'],
            'amount' => ['required', 'numeric'],
            'module' => ['required', 'in:pos,online'],
            'online_type' => ['sometimes', 'in:all,app,web'],
            'branches' => ['required', 'array'],
            'branches.*' => ['required', 'exists:branches,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $serviceFeesRequest = $validator->validated();
        $service_fees = $this->service_fees
        ->where("id", $id)
        ->first();
        if(!$service_fees){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $service_fees->branches()->sync($request->branches);
        $service_fees->update([
            "type" => $request->type,
            "amount" => $request->amount,
            "module" => $request->module,
            "online_type" => $request->online_type,
        ]);

        return response()->json([
            "success" => "You update service fees success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->service_fees
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete Service Fees success"
        ]);
    }
}
