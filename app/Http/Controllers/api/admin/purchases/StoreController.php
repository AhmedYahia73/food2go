<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStore;
use App\Models\Branch;

class StoreController extends Controller
{
    public function __construct(private PurchaseStore $store,
    private Branch $branches){}

    public function view(Request $request){
        $stores = $this->store
        ->get();
        $branches = $this->branches
        ->select('id', 'name')
        ->where('status', 1)
        ->get();

        return response()->json([
            'stores' => $stores,
            'branches' => $branches,
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
            'branches' => ['required', 'array'],
            'branches.*' => ['exists:branches,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $storeRequest = $validator->validated();
        $store = $this->store
        ->create($storeRequest);
        $store->branches()->attach($request->branches);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'location' => ['required'],
            'status' => ['required', 'boolean'],
            'branches' => ['required', 'array'],
            'branches.*' => ['exists:branches,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $storeRequest = $validator->validated();
        $store = $this->store
        ->where('id', $id)
        ->first();
        $store->update($storeRequest);
        $store->branches()->attach($request->branches);

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
