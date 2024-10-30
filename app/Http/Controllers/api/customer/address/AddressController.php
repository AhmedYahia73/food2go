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

    public function modify(AddressRequest $request, $id){
        // https://backend.food2go.pro/customer/address/update/{id}
        // Keys
        // zone_id, address, street, building_num, floor_num, apartment, additional_data, type
        $address_request = $request->only($this->AddressRequest);
        $this->address
        ->where('id', $id)
        ->update($address_request);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
