<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\cashier_man\CasheirManRequest;
use Illuminate\Support\Facades\Validator;

use App\Models\Cashier;
use App\Models\CashierMan;
use App\Models\CashierRole;
use App\Models\Branch;

class CashierManController extends Controller
{
    public function __construct(private Cashier $cashier,
    private CashierMan $cashier_men, private Branch $branch,
    private CashierRole $cashier_role){}

    public function view(Request $request){
        // /admin/cashier_man
        $cashier = $this->cashier
        ->get();
        $cashier_men = $this->cashier_men
        ->with('branch', 'roles')
        ->get();
        $branches = $this->branch
        ->where('status', 1)
        ->get();
        $roles = [
            'branch_reports',
            'all_reports',
            'table_status',
        ];

        return response()->json([
            'cashiers' => $cashier,
            'cashier_men' => $cashier_men,
            'branches' => $branches,
            'roles' => $roles,
        ]);
    }

    public function status(Request $request, $id){
        // admin/cashier_man/status/{id}
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
        $cashier_men = $this->cashier_men
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]); 

        return response()->json([
            'success' => $request->status ? 'active' : 'banned',
        ]);
    }
    
    public function cashier_man(Request $request, $id){
        // /admin/cashier_man/item/{id}
        $cashier_man = $this->cashier_men
        ->with('branch')
        ->where('id', $id)
        ->first();

        return response()->json([
            'cashier_man' => $cashier_man,
        ]);
    }

    public function create(CasheirManRequest $request){
        // admin/cashier_man/add
        // Keys
        // user_name, password, branch_id, status,
        // take_away, dine_in, delivery, car_slow
        // roles[]
        $validation = Validator::make($request->all(), [
            'roles.*' => ['in:branch_reports,all_reports,table_status'],
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $request->validated(); 
        $cashier_men = $this->cashier_men
        ->create($cashierRequest);
        if ($request->roles) {
            foreach ($request->roles as $item) {
                $this->cashier_role
                ->create([
                    'roles' => $item,
                    'cashier_man_id' => $cashier_men->id,
                ]);
            }
        }

        return response()->json([
            'success' => $cashier_men,
        ]);
    }

    public function modify(CasheirManRequest $request, $id){
        // admin/cashier_man/update/{id}
        // user_name, password, branch_id, status
        // take_away, dine_in, delivery, car_slow
        // roles[]
        $validation = Validator::make($request->all(), [
            'roles.*' => ['in:branch_reports,all_reports,table_status'],
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $request->validated(); 
        $cashier_men = $this->cashier_men
        ->where('id', $id)
        ->first();
        if (empty($cashier_men)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        $cashier_men->update($cashierRequest);
        $this->cashier_role
        ->where('cashier_man_id', $id)
        ->delete();
        if ($request->roles) {
            foreach ($request->roles as $item) {
                $this->cashier_role
                ->create([
                    'roles' => $item,
                    'cashier_man_id' => $id,
                ]);
            }
        }

        return response()->json([
            'success' => $cashier_men,
        ]);
    }

    public function delete(Request $request, $id){
        // admin/cashier_man/delete/{id}   
        $cashier = $this->cashier_men
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
