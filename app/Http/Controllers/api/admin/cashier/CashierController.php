<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Cashier;

class CashierController extends Controller
{
    public function __construct(private Cashier $cashier){}

    public function view(Request $request){
        // /admin/cashier
        $cashier = $this->cashier
        ->get();

        return response()->json([
            'cashiers' => $cashier,
        ]);
    }

    public function status(Request $request, $id){
        // admin/cashier/status/{id}
        // Keys
        // status
        $validation = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashier = $this->cashier
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]); 

        return response()->json([
            'success' => $request->status ? 'active' : 'banned',
        ]);
    }
    
    public function cashier(Request $request, $id){
        // /admin/cashier/item/{id}
        $cashier = $this->cashier 
        ->where('id', $id)
        ->first();

        return response()->json([
            'cashier' => $cashier,
        ]);
    }

    public function create(Request $request){
        // admin/cashier/add
        // Keys
        // name, branch_id, status
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $validation->validated();
        $cashier = $this->cashier
        ->create($cashierRequest);

        return response()->json([
            'success' => $cashier,
        ]);
    }

    public function modify(Request $request, $id){
        // admin/cashier/update/{id}
        // Keys
        // name, branch_id, status
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $validation->validated();
        $cashier = $this->cashier
        ->where('id', $id)
        ->first();
        if (empty($cashier)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        $cashier->update($cashierRequest);

        return response()->json([
            'success' => $cashier,
        ]);
    }

    public function delete(Request $request, $id){
        // admin/cashier/delete/{id}   
        $cashier = $this->cashier
        ->where('id', $id)
        ->first();
        if (empty($cashier)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        $cashier->delete();

        return response()->json([
            'success' => 'You delete cashier success'
        ], 200);
    }
}
