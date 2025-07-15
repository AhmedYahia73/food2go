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
use App\Models\CompanyInfo;
use App\Models\Setting;

class AddressController extends Controller
{
    public function __construct(private Address $address, private Zone $zones, 
    private User $user, private Branch $branch, private City $city, 
    private CompanyInfo $company_info, private Setting $settings){}
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
        $locale = $request->locale ?? 'en';
        $addresses = $this->address
        ->whereHas('address', function($query) use($request){
            $query->where('users.id', $request->user()->id);
        })
        ->with('zone')
        ->get()
        ->map(function($item) use($locale){
            $item->zone->zone = $item->zone->translations->where('key', $item->zone->zone)
            ->where('locale', $locale)->first()?->value ?? $item->zone->zone;
            $item->branch_status = $item?->zone?->branch?->status;
            $item->block_reason = $item?->zone?->branch?->block_reason;
            return $item;
        });
        $zones = $this->zones
        ->where('status', 1)
        ->get()
        ->map(function($item) use($locale){
            return [
                'id' => $item->id,
                'zone' => $item->translations->where('key', $item->zone)
                ->where('locale', $locale)->first()?->value ?? $item->zone,
                'price' => $item->price,
                'status' => $item->status,
                'city_id' => $item->city_id,
                'branch_id' => $item->branch_id,
            ];
        });
        $branches = $this->branch
        ->get();
        $cities = $this->city
        ->where('status', 1)
        ->get()
        ->map(function($item) use($locale){
            return [
                'id' => $item->id,
                'name' => $item->translations->where('key', $item->name)
                ->where('locale', $locale)->first()?->value ?? $item->name, 
                'status' => $item->status, 
            ];
        });
        $call_center_phone = $this->company_info
        ->orderByDesc('id')
        ->first()?->phone; 

        $order_types = $this->settings
        ->where('name', 'order_type')
        ->first();  
        if (empty($order_types)) {
            $order_types = $this->settings
            ->create([
                'name' => 'order_type',
                'setting' => json_encode([
                    [
                        'id' => 1,
                        'type' => 'take_away',
                        'status' => 1
                    ],
                    [
                        'id' => 2,
                        'type' => 'dine_in',
                        'status' => 1
                    ],
                    [
                        'id' => 3,
                        'type' => 'delivery',
                        'status' => 1
                    ]
                ]),
            ]);
        }
        $order_types = $order_types->setting;
        $order_types = json_decode($order_types);

        return response()->json([
            'addresses' => $addresses,
            'zones' => $zones,
            'branches' => $branches,
            'call_center_phone' => $call_center_phone,
            'cities' => $cities,
            'order_types' => $order_types,
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
