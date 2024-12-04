<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\ZoneRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Branch;
use App\Models\City;
use App\Models\Zone;

class ZoneController extends Controller
{
    public function __construct(private Branch $branches, private City $cities,
    private Zone $zones){}
    protected $zoneRequest = [
        'city_id',
        'branch_id',
        'price',
        'zone',
        'status',
    ];

    public function view(){
        // https://bcknd.food2go.online/admin/settings/zone
        $branches = $this->branches
        ->get();
        $cities = $this->cities
        ->get();
        $zones = $this->zones
        ->with(['city', 'branch'])
        ->get();

        return response()->json([
            'branches' => $branches,
            'cities' => $cities,
            'zones' => $zones,
        ]);
    }

    public function zone($id){
        // https://bcknd.food2go.online/admin/settings/zone/item/{id}
        $zones = $this->zones
        ->where('id', $id)
        ->with(['city', 'branch'])
        ->first();

        return response()->json([
            'zones' => $zones,
        ]);
    }

    public function status($id, Request $request){
        // https://bcknd.food2go.online/admin/settings/zone/status/{id}
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

        $this->zones
        ->where('id', $id)
        ->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => $request->status ? 'approve' : 'banned'
        ]);
    }

    public function create(ZoneRequest $request){
        // https://bcknd.food2go.online/admin/settings/zone/add
        // Keys
        // city_id, branch_id, price, zone
        $zone_request = $request->only($this->zoneRequest);
        $zone = $this->zones
        ->create($zone_request);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(ZoneRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/zone/update/{id}
        // Keys
        // city_id, branch_id, price, zone
        $zone_request = $request->only($this->zoneRequest);
        $this->zones
        ->where('id', $id)
        ->update($zone_request);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/settings/zone/delete/{id}
        $this->zones
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
