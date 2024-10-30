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
        $address->zone_id = $address_request->zone_id ?? $address->zone_id;
        $address->address = $address_request->address ?? $address->address;
        $address->street = $address_request->street ?? $address->street;
        $address->building_num = $address_request->building_num ?? $address->building_num;
        $address->floor_num = $address_request->floor_num ?? $address->floor_num;
        $address->apartment = $address_request->apartment ?? $address->apartment;
        $address->additional_data = $address_request->additional_data ?? $address->additional_data;
        $address->type = $address_request->type ?? $address->type;
        $address->save();

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
