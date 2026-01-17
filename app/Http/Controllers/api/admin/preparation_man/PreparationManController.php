<?php

namespace App\Http\Controllers\api\admin\preparation_man;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PreparationMan;
use App\Models\Branch;

class PreparationManController extends Controller
{
    public function __construct(private PreparationMan $preparation_man,
    private Branch $branches){}

    public function view(Request $request, $id){
        $preparation_men = $this->preparation_man
        ->with("branch")
        ->where("branch_id", $id)
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
        
                'print_name' => $item->print_name,
                'print_ip' => $item->print_ip,
                'print_status' => $item->print_status,
                'print_type' => $item->print_type,
                'print_port' => $item->print_port,
            ];
        });

        return response()->json([
            'preparation_men' => $preparation_men
        ]);
    }

    public function lists(Request $request){
        $branches = $this->branches
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "branches" => $branches
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
                'print_name' => $preparation_man->print_name,
                'print_ip' => $preparation_man->print_ip,
                'print_status' => $preparation_man->print_status,
                'print_type' => $preparation_man->print_type,
                'print_port' => $preparation_man->print_port,
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
            'name' => 'required|unique:preparation_men,name',
            'password' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
            
            'print_name' => ['sometimes'],
            'print_ip' => ['sometimes'],
            'print_status' => ['sometimes', 'boolean'],
            'print_type' => ['sometimes', 'in:usb,network'],
            'print_port' => ['sometimes'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $preparationRequest = $validator->validated();
        $this->preparation_man
        ->create($preparationRequest);

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:preparation_men,name,' . $id,
            'password' => 'sometimes',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
            'print_name' => ['sometimes'],
            'print_ip' => ['sometimes'],
            'print_status' => ['sometimes', 'boolean'],
            'print_type' => ['sometimes', 'in:usb,network'],
            'print_port' => ['sometimes'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $preparationRequest = $validator->validated();
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
