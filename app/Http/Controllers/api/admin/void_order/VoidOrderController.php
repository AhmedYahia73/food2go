<?php

namespace App\Http\Controllers\api\admin\void_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Validator; 

use App\Models\VoidReason; 

class VoidOrderController extends Controller
{
    public function __construct(private VoidReason $void_reason){}


    public function view(){
        // /admin/void_reason
        $void_reason = $this->void_reason
        ->get();

        return response()->json([
            'void_reason' => $void_reason, 
        ]);
    }

    public function void_reason($id){
        // /admin/void_reason/item/{id}
        $void_reason = $this->void_reason
        ->where('id', $id)
        ->first();

        return response()->json([
            'void_reason' => $void_reason, 
        ]);
    }

    public function status(Request $request, $id){
        // /admin/void_reason/status/{id}
        $validator = Validator::make($request->all(), [ 
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $void_reason = $this->void_reason 
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        // /admin/void_reason/add
        $validator = Validator::make($request->all(), [
            'void_reason' => ['required'], 
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $reasonRequest = $validator->validated();
        $void_reason = $this->void_reason
        ->create($reasonRequest); 

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // /admin/void_reason/update/{id}
        $validator = Validator::make($request->all(), [
            'void_reason' => ['required'], 
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $reasonRequest = $validator->validated();
        $void_reason = $this->void_reason
        ->where('id', $id)
        ->update($reasonRequest); 

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $this->void_reason
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
