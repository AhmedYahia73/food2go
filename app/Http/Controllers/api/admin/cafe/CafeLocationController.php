<?php

namespace App\Http\Controllers\api\admin\cafe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\CafeLocation;

class CafeLocationController extends Controller
{
    public function __construct(private CafeLocation $locations){}

    public function view(){
        // /admin/caffe_location
        $locations = $this->locations
        ->get();

        return response()->json([
            'locations' => $locations
        ]);
    }
    
    public function location($id){
        // /admin/caffe_location/item/{id}
        $location = $this->locations
        ->where('id', $id)
        ->first();

        return response()->json([
            'location' => $location
        ]);
    }

    public function create(Request $request){
        // /admin/caffe_location/add
        // Keys
        // name
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }

        $this->locations
        ->create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // /admin/caffe_location/update/{id}
        // Keys
        // name
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }

        $this->locations
        ->where('id', $id)
        ->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){ 
        // /admin/caffe_location/delete/{id}
        $this->locations
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
