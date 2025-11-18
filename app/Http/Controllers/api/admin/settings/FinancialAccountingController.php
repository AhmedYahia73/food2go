<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\FinancialRequest;
use Illuminate\Support\Facades\Validator;
use App\trait\image;
 
use App\Models\FinantiolAcounting;
use App\Models\Branch;

class FinancialAccountingController extends Controller
{
    use image;
    public function __construct(private FinantiolAcounting $financial,
    private Branch $branches){}

    public function view(Request $request){
        // /admin/financial
        $financial = $this->financial
        ->with('branch')
        ->get();
        $branches = $this->branches
        ->where('status', 1)
        ->get();

        return response()->json([
            'financials' => $financial,
            'branches' => $branches,
        ]);
    }

    public function status(Request $request, $id){
        // admin/financial/status/{id}
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
        $financial = $this->financial
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]); 

        return response()->json([
            'success' => $request->status ? 'active' : 'banned',
        ]);
    }
    
    public function financial(Request $request, $id){
        // /admin/financial/item/{id}
        $financial = $this->financial 
        ->where('id', $id)
        ->with('branch')
        ->first();

        return response()->json([
            'financial' => $financial,
        ]);
    }

    public function create(FinancialRequest $request){
        // admin/financial/add
        // Keys
        // name, details, balance, status, logo, branch_id
        $validation = Validator::make($request->all(), [
            'logo' => 'required',
            'balance' => 'required|numeric',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $financialRequest = $request->validated();
        if (!is_string($request->logo)) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/financial/logo');
            $financialRequest['logo'] = $image_path;
        }
        $financialRequest['start_balance'] = $request->balance;
        $financialRequest['balance'] = $request->balance;
        $financial = $this->financial
        ->create($financialRequest);
        $financial->branch()->attach($request->branch_id);

        return response()->json([
            'success' => $financial,
        ]);
    }

    public function modify(FinancialRequest $request, $id){
        // admin/financial/update/{id}
        // Keys
        // name, details, balance, currency_id, status, logo, branch_id
        $financialRequest = $request->validated();
        $financial = $this->financial
        ->where('id', $id)
        ->first();
        if (empty($financial)) {
            return response()->json([
                'errors' => 'financial is not found'
            ], 400);
        }
        if (!is_string($request->logo)) {
            $image_path = $this->update_image($request, $financial->logo, 'logo', 'admin/settings/financial/logo');
            $financialRequest['logo'] = $image_path;
        }
        $financial->update($financialRequest);
        $financial->branch()->sync($request->branch_id);

        return response()->json([
            'success' => $financial,
        ]);
    }

    public function delete(Request $request, $id){
        // admin/financial/delete/{id}   
        $financial = $this->financial
        ->where('id', $id)
        ->first();
        if (empty($financial)) {
            return response()->json([
                'errors' => 'financial is not found'
            ], 400);
        }
        $this->deleteImage($financial->logo);
        $financial->delete();

        return response()->json([
            'success' => 'You delete financial success'
        ], 200);
    }
}
