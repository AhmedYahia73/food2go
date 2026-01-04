<?php

namespace App\Http\Controllers\api\cashier\address;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Address;
use App\Models\Zone;

class AddressController extends Controller
{
    public function __construct(private Address $address, 
    private Zone $zone){}
    
    public function customer_address($id){
        // /cashier/address/customer_address/{id}
        $locale = $request->locale ?? "ar";
        $address = $this->address
        ->with('zone.city:id,name')
        ->whereHas('users', function($query) use($id){
            $query->where('users.id', $id);
        })
        ->first(); 
        $zones = $this->zone
        ->where('status', 1)
        ->with("translations")
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "city_id" => $item->city_id,
                "branch_id" => $item->branch_id,
                "price" => $item->price,
                "zone" => $item->translations
                ->where("locale", $locale)
                ->where("key", $item->zone)
                ->first()?->value ?? $item->zone,
                "status" => $item->status,
            ]; 
        });

        return response()->json([
            'address' => $address,
            'zones' => $zones,
        ]);
    }
    
    public function address($id){
        // /cashier/address/item/{id}
        $locale = $request->locale ?? "ar";
        $address = $this->address
        ->with('zone.city:id,name')
        ->where('id', $id)
        ->first();
        $zones = $this->zone
        ->where('status', 1)
        ->with("translations")
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "city_id" => $item->city_id,
                "branch_id" => $item->branch_id,
                "price" => $item->price,
                "zone" => $item->translations
                ->where("locale", $locale)
                ->where("key", $item->zone)
                ->first()?->value ?? $item->zone,
                "status" => $item->status,
            ]; 
        });

        return response()->json([
            'address' => $address,
            'zones' => $zones,
        ]);
    }

    public function create(Request $request){
        // /cashier/address/add
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
        $address = $this->address->create($addressRequest);
        $address->users()->attach($request->customer_id);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // /cashier/address/update/{id}
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
        $address = $this->address
        ->where('id', $id)
        ->first();
        $address->update($addressRequest);
        $address->users()->sync($request->customer_id);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
