<?php

namespace App\Http\Controllers\api\admin\preparation_man;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PreparationMan;

class PreparationManController extends Controller
{
    public function __construct(private PreparationMan $preparation_man){}

    public function view(Request $request){
        $preparation_men = $this->preparation_man
        ->with("branch")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "status" => $item->status,
                "branch" => [
                    "id" => $item?->branch?->id,
                    "name" => $item?->branch?->name,
                ], 
            ];
        });

        return response()->json([
            'preparation_men' => $preparation_men
        ]);
    }

    public function preparation_man(Request $request, $id){
        $preparation_man = $this->preparation_man
        ->where("id", $id)
        ->with("branch")
        ->first();

        return response()->json([
            'data' => [ 
                "id" => $preparation_man->id,
                "name" => $preparation_man->name,
                "status" => $preparation_man->status,
                "branch" => [
                    "id" => $preparation_man?->branch?->id,
                    "name" => $preparation_man?->branch?->name,
                ], 
            ]
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

        $this->preparation_man
        ->where("id", $id)
        ->update([
            "status" => $request->status
        ]);

        return response()->json([
            "success" => "You update status success"
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'password' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $preparationRequest = $request->validated();
        $this->preparation_man
        ->create($preparationRequest);

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'password' => 'sometimes',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $preparationRequest = $request->validated();
        if(!empty($request->password)){
            $preparationRequest['password'] = bcrypt($request->password);
        }
        $this->preparation_man
        ->where("id", $id)
        ->update($preparationRequest);

        return response()->json([
            "success" => "You update data success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->preparation_man
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
