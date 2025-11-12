<?php

namespace App\Http\Controllers\api\branch\financial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\FinancialRequest;
use Illuminate\Support\Facades\Validator;
use App\trait\image;
 
use App\Models\FinantiolAcounting;
use App\Models\Branch;

class FinancialController extends Controller
{
    use image;
    public function __construct(private FinantiolAcounting $financial ){}

    public function view(Request $request){
        // /admin/financial
        $financial = $this->financial
        ->whereHas('branch', function($query) use($request){
            $query->where('branches.id', $request->user()->id);
        })
        ->get(); 

        return response()->json([
            'financials' => $financial, 
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
        ->whereHas('branch', function($query) use($request){
            $query->where('branches.id', $request->user()->id);
        })
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
        ->whereHas('branch', function($query) use($request){
            $query->where('branches.id', $request->user()->id);
        })
        ->first();

        return response()->json([
            'financial' => $financial,
        ]);
    }

    public function create(FinancialRequest $request){
        // admin/financial/add
        // Keys
        // name, details, balance, status, logo, branch_id
        $financial = Validator::make($request->all(), [
            'name' => ['required'],
            'details' => ['required'],
            'discount' => ['required', 'boolean'],
            'balance' => ['required', 'numeric'],
            'description_status' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ]);
        if ($financial->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $financial->errors(),
            ],400);
        }
        $validation = Validator::make($request->all(), [
            'logo' => 'required',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $financialRequest = $financial->validated();
        if (!is_string($request->logo)) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/financial/logo');
            $financialRequest['logo'] = $image_path;
        }
        $financial = $this->financial
        ->create($financialRequest);
        $financial->branch()->attach($request->user()->id);

        return response()->json([
            'success' => $financial,
        ]);
    } 

    public function delete(Request $request, $id){
        // admin/financial/delete/{id}
        $financial = $this->financial
        ->where('id', $id)
        ->whereHas('branch', function($query) use($request){
            $query->where('branches.id', $request->user()->id);
        })
        ->first();
        if (empty($financial)) {
            return response()->json([
                'errors' => 'financial is not found'
            ], 400);
        }
        $financial->branch()->detach($request->user()->id);

        return response()->json([
            'success' => 'You delete financial success'
        ], 200);
    }
}
