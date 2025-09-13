<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStore;

class StoreController extends Controller
{
    public function __construct(private PurchaseStore $store){}

    public function view(Request $request){
        $stores = $this->store
        ->get();

        return response()->json([
            'stores' => $stores
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

        $this->store
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'location' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $storeRequest = $validator->validated();
        $this->store
        ->create($storeRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'location' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $storeRequest = $validator->validated();
        $this->store
        ->where('id', $id)
        ->update($storeRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        $this->store
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
