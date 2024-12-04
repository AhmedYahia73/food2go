<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\City;

class CityController extends Controller
{
    public function __construct(private City $cities){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/city
        $cities = $this->cities
        ->get();

        return response()->json([
            'cities' => $cities
        ]);
    }

    public function city($id){
        // https://bcknd.food2go.online/admin/settings/city/item/{id}
        $city = $this->cities
        ->where('id', $id)
        ->first();

        return response()->json([
            'city' => $city
        ]);
    }

    public function status($id, Request $request){
        // https://bcknd.food2go.online/admin/settings/city/status/{id}
        // Key
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->cities
        ->where('id', $id)
        ->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => $request->status ? 'approve' : 'banned'
        ]);
    }

    public function create(Request $request){
        // https://bcknd.food2go.online/admin/settings/city/add
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->cities
        ->create([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/admin/settings/city/update/{id}
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->cities
        ->where('id', $id)
        ->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/settings/city/delete/{id}
        $this->cities
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
