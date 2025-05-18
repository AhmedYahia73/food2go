<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Address;

class PosAddressController extends Controller
{
    public function __construct(private Address $address){}
    
    public function address($id){
        // /admin/pos/address/item/{id}
        $address = $this->address
        ->with('zone.city:id,name')
        ->where('id', $id)
        ->first();

        return response()->json([
            'address' => $address,
        ]);
    }

    public function create(Request $request){
        // /admin/pos/address/add
        // Keys
        // customer_id, zone_id, address, street, building_num, floor_num, 
        // apartment, additional_data, type, map
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'zone_id' => 'required|exists:zones,id',
            'address' => 'required',
            'street' => 'sometimes',
            'building_num' => 'sometimes',
            'floor_num' => 'sometimes',
            'apartment' => 'sometimes',
            'additional_data' => 'sometimes',
            'type' => 'sometimes',
            'map' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'errors' => $validator->errors(),
            ],400);
        }
        $addressRequest = $validator->validated();
        $this->address->create($addressRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // /admin/pos/address/update/{id}
        // Keys
        // zone_id, address, street, building_num, floor_num, 
        // apartment, additional_data, type, map
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id',
            'address' => 'required',
            'street' => 'sometimes',
            'building_num' => 'sometimes',
            'floor_num' => 'sometimes',
            'apartment' => 'sometimes',
            'additional_data' => 'sometimes',
            'type' => 'sometimes',
            'map' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'errors' => $validator->errors(),
            ],400);
        }
        $addressRequest = $validator->validated();
        $this->address->create($addressRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
