<?php

namespace App\Http\Controllers\api\cashier\user;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\User;
use App\Models\City;
use App\Models\Zone;

class AddressController extends Controller
{
    public function __construct(private Address $address,
    private User $user, private City $cities, private Zone $zones){}

    public function view(Request $request, $id){
        $addresses = $this->address
        ->whereHas('users', function($query) use($id){
            $query->where('users.id', $id);
        })
        ->with(['zone:id,zone,price', 'city:id,name'])
        ->get();

        return response()->json([
            'addresses' => $addresses,
        ]);
    }

    public function lists(Request $request){
        $locale = $request->locale ?? "ar";
        $cities = $this->cities
        ->select('id', 'name')
        ->where('status', 1)
        ->get()
        ->map(function($item) use($locale){
            return [ 
                "id" => $item->id,
                "name" => $item->translations
                ->where("locale", $locale)
                ->where("key", $item->name)
                ->first()?->value ?? $item->name,
                "status" => $item->status,
            ]; 
        });
        $zones = $this->zones
        ->select('id', 'zone', 'city_id')
        ->where('status', 1)
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
            'cities' => $cities,
            'zones' => $zones,
        ]);
    }

    public function create(Request $request, $id){
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
            'city_id' => 'required|exists:cities,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $addressRequest = $validator->validated();
        $user = $this->user
        ->where('id', $id)
        ->first();
        $user->address()->create($addressRequest);

        return response()->json([
            'success' => 'You add data sucess'
        ]);
    }

    public function modify(Request $request, $id){
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
            'city_id' => 'required|exists:cities,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
  
        $addresses = $this->address
        ->where('id', $id)
        ->first();
        $addresses->zone_id = $request->zone_id ?? $addresses->zone_id;
        $addresses->address = $request->address ?? $addresses->address;
        $addresses->street = $request->street ?? $addresses->street;
        $addresses->building_num = $request->building_num ?? $addresses->building_num;
        $addresses->floor_num = $request->floor_num ?? $addresses->floor_num;
        $addresses->apartment = $request->apartment ?? $addresses->apartment;
        $addresses->additional_data = $request->additional_data ?? $addresses->additional_data;
        $addresses->type = $request->type ?? $addresses->type;
        $addresses->map = $request->map ?? $addresses->map;
        $addresses->city_id = $request->city_id ?? $addresses->city_id;
        $addresses->save();

        return response()->json([
            'success' => 'You update data sucess'
        ]);
    }

    public function delete(Request $request, $id){ 
        $addresses = $this->address
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
