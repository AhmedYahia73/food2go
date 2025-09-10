<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Purchase;

class PurchaseController extends Controller
{  
    public function __construct(private Purchase $purchases){}

    public function view(Request $request){
        $purchases = $this->purchases
        ->select('id', 'name', 'description', 'price', 'quantity', 'created_at')
        ->get();

        return response()->json([
            'purchases' => $purchases
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'price' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $purchaseRequest = $validator->validated();
        $this->purchases
        ->create($purchaseRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'price' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $purchaseRequest = $validator->validated();
        $this->purchases
        ->where('id', $id)
        ->update($purchaseRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
          $this->purchases
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
