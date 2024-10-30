<?php

namespace App\Http\Controllers\api\customer\address;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\address\AddressRequest;

use App\Models\Address;
use App\Models\Zone;

class AddressController extends Controller
{
    public function __construct(private Address $address, private Zone $zones){}
    protected $AddressRequest = [
        'zone_id',
        'address',
        'street',
        'building_num',
        'floor_num',
        'apartment',
        'additional_data',
        'type',
    ];

    public function view(){
        // https://backend.food2go.pro/customer/address
        $addresses = $this->address
        ->with('zone')
        ->get();
        $zones = $this->zones->get();

        return response()->json([
            'addresses' => $addresses,
            'zones' => $zones,
        ]);
    }

    public function add(AddressRequest $request){
        // https://backend.food2go.pro/customer/address/add
        // Keys
        // zone_id, address, street, building_num, floor_num, apartment, additional_data, type
        $address_request = $request->only($this->AddressRequest);
        $this->address
        ->create($address_request);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://backend.food2go.pro/customer/address/update/{id}
        // Keys
        // zone_id, address, street, building_num, floor_num, apartment, additional_data, type
        $address = $this->address
        ->where('id', $id)
        ->first();
        if (empty($address)) {
            return response()->json([
                'faild' => 'Address is not fount'
            ], 400);
        } 
        $address->zone_id = $request->zone_id ?? $address->zone_id;
        $address->address = $request->address ?? $address->address;
        $address->street = $request->street ?? $address->street;
        $address->building_num = $request->building_num ?? $address->building_num;
        $address->floor_num = $request->floor_num ?? $address->floor_num;
        $address->apartment = $request->apartment ?? $address->apartment;
        $address->additional_data = $request->additional_data ?? $address->additional_data;
        $address->type = $request->type ?? $address->type;
        $address->save();

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
