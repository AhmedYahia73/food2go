<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\FinancialRequest;
use Illuminate\Support\Facades\Validator;
use App\trait\image;
 
use App\Models\FinantiolAcounting;

class FinancialAccountingController extends Controller
{
    use image;
    public function __construct(private FinantiolAcounting $financial){}

    public function view(Request $request){
        // /admin/financial
        $financial = $this->financial
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
        ->first();

        return response()->json([
            'financial' => $financial,
        ]);
    }

    public function create(FinancialRequest $request){
        // admin/financial/add
        // Keys
        // name, details, balance, status, logo
        $financialRequest = $request->validated();
        if (!is_string($request->logo)) {
            $image_path = $this->upload($request, 'logo', 'admin/settings/financial/logo');
            $financialRequest['logo'] = $image_path;
        }
        $financial = $this->financial
        ->create($financialRequest);

        return response()->json([
            'success' => $financial,
        ]);
    }

    public function modify(FinancialRequest $request, $id){
        // admin/financial/update/{id}
        // Keys
        // name, details, balance, currency_id, status, logo
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
