<?php

namespace App\Http\Controllers\api\admin\unit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Unit;

class UnitController extends Controller
{
    public function __construct(private Unit $unit){}

    public function view(Request $request){
        $units = $this->unit
        ->get();

        return response()->json([
            "units" => $units
        ]);
    }

    public function unit_item(Request $request, $id){
        $unit = $this->unit
        ->where("id", $id)
        ->first();

        return response()->json([
            "unit" => $unit
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

        $unit = $this->unit
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
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $unitRequest = $validator->validated();
        $unit = $this->unit
        ->create($unitRequest);

        return response()->json([
            "success" => "You add unit success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $unitRequest = $validator->validated();
        $unit = $this->unit
        ->create($unitRequest);

        return response()->json([
            "success" => "You update unit success"
        ]);
    }

    public function delete(Request $request, $id){
        $unit = $this->unit
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete unit success"
        ]);
    }
}
