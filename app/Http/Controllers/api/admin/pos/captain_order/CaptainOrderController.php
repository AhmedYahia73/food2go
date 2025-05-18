<?php

namespace App\Http\Controllers\api\admin\pos\captain_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\pos\CaptainOrderRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\CaptainOrder;
use App\Models\CafeLocation;
use App\Models\Branch;

class CaptainOrderController extends Controller
{
    public function __construct(private Branch $branches, 
    private CaptainOrder $captain_order, private CafeLocation $cafe_locations){}

    public function view(){
        // /admin/pos/captain
        $captain_order = $this->captain_order
        ->with('branch', 'locations')
        ->get();
        $branches = $this->branches
        ->get();
        $cafe_locations = $this->cafe_locations
        ->get();

        return response()->json([
            'captain_order' => $captain_order,
            'branches' => $branches,
            'cafe_locations' => $cafe_locations
        ]);
    }

    public function captain($id){
        // /admin/pos/captain/item/{id}
        $captain_order = $this->captain_order
        ->with('branch', 'locations')
        ->where('id', $id)
        ->first();

        return response()->json([
            'captain_order' => $captain_order
        ]);
    }

    public function create(CaptainOrderRequest $request){
        // /admin/pos/captain/add
        $validator = Validator::make($request->all(), [
            'email' => 'unique:captain_orders,email',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $captainRequest = $request->validated();
        $captain_order = $this->captain_order
        ->create($captainRequest);
        if ($request->locations) { 
            $captain_order->locations()->attach($request->locations); 
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(CaptainOrderRequest $request, $id){
        // /admin/pos/captain/update/{id}
        $validator = Validator::make($request->all(), [
            'email' => Rule::unique('captain_orders')->ignore($id),
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $captainRequest = $request->validated();
        $captain_order = $this->captain_order
        ->where('id', $id)
        ->first();
        $captain_order->update($captainRequest);
        if ($request->locations) { 
            $captain_order->locations()->sync($request->locations); 
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $this->captain_order
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
