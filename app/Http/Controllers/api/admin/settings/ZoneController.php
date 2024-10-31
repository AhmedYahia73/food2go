<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\ZoneRequest;

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
    ];

    public function view(){
        // https://backend.food2go.pro/admin/settings/zone
        $branches = $this->branches
        ->get();
        $cities = $this->cities
        ->get();
        $zones = $this->zones
        ->get();

        return response()->json([
            'branches' => $branches,
            'cities' => $cities,
            'zones' => $zones,
        ]);
    }

    public function create(ZoneRequest $request){
        // https://backend.food2go.pro/admin/settings/zone/add
        $zone_request = $request->only($this->zoneRequest);
        $zone = $this->zones
        ->create($zone_request);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(ZoneRequest $request, $id){
        // https://backend.food2go.pro/admin/settings/zone/update/{id}
        $zone_request = $request->only($this->zoneRequest);
        $this->zones
        ->where('id', $id)
        ->update($zone_request);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://backend.food2go.pro/admin/settings/zone/delete/{id}
        $this->zones
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
