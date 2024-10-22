<?php

namespace App\Http\Controllers\api\admin\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\deal\DealRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Deal;

class DealController extends Controller
{
    public function __construct(private Deal $deals){}

    public function view(){
        $deals = $this->deals
        ->with('times')
        ->get();

        return response()->json([
            'deals' => $deals
        ]);
    }

    public function status(Request $request ,$id){
         // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $this->deals->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        if ($request->status == 0) {
            return response()->json([
                'success' => 'banned'
            ]);
        } else {
            return response()->json([
                'success' => 'active'
            ]);
        }
    }

    public function create(DealRequest $request){
        
    }

    public function modify(DealRequest $request, $id){
        
    }

    public function delete($id){
        
    }
}
