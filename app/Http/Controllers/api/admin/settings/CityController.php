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
        // https://backend.food2go.pro/admin/settings/city
        $cities = $this->cities
        ->get();

        return response()->json([
            'cities' => $cities
        ]);
    }

    public function create(Request $request){
        // https://backend.food2go.pro/admin/settings/city/add
        //Key
        // name
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->cities
        ->create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://backend.food2go.pro/admin/settings/city/update/{id}
        //Key
        // name
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->cities
        ->where('id', $id)
        ->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://backend.food2go.pro/admin/settings/city/delete/{id}
        $this->cities
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
