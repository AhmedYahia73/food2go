<?php

namespace App\Http\Controllers\api\customer\address;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\address\AddressRequest;

use App\Models\Address;
use App\Models\Zone;
use App\Models\User;
use App\Models\Branch;
use App\Models\City;

class AddressController extends Controller
{
    public function __construct(private Address $address, private Zone $zones, 
    private User $user, private Branch $branch, private City $city){}
    protected $AddressRequest = [
        'zone_id',
        'address',
        'street',
        'building_num',
        'floor_num',
        'apartment',
        'additional_data',
        'type',
        'map',
        'city_id',
    ];

    public function view(Request $request){
        // https://bcknd.food2go.online/customer/address
        $local = $request->local ?? 'en';
        $addresses = $this->user
        ->where('id', $request->user()->id)
        ->with('address.zone')
        ->first()->toArray(); 
        $zones = $this->zones
        ->where('status', 1)
        ->get()
        ->map(function($item) use($local){
            return [
                'zone' => $item->translations->where('key', $item->zone)
                ->where('locale', $local)->first()?->value ?? $item->zone,
                'price' => $item->price,
                'status' => $item->status,
                'city_id' => $item->city_id,
                'branch_id' => $item->branch_id,
            ];
        });
        $branches = $this->branch
        ->where('status', 1)
        ->get();
        $cities = $this->city
        ->where('status', 1)
        ->get();

        return response()->json([
            'addresses' => $addresses['address'],
            'zones' => $zones,
            'branches' => $branches,
            'cities' => $cities,
        ]);
    }

    public function add(AddressRequest $request){
        // https://bcknd.food2go.online/customer/address/add
        // Keys
        // zone_id, address, street, building_num, floor_num, apartment, additional_data, type, city_id
        $address_request = $request->only($this->AddressRequest);
        $address = $this->address
        ->create($address_request);
        $request->user()->address()->attach($address->id);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/customer/address/update/{id}
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
        $address->map = $request->map ?? $address->map;
        $address->city_id = $request->city_id ?? $address->city_id;
        $address->save();

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/customer/address/delete/{id}
        $address = $this->address
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
