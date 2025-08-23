<?php

namespace App\Http\Controllers\api\admin\waiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\pos\CaptainOrderRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Waiter;
use App\Models\CafeLocation;
use App\Models\Branch;

class WaiterController extends Controller
{
    public function __construct(private Branch $branches, 
    private Waiter $waiter, private CafeLocation $cafe_locations){}


    public function view(){
        // /admin/pos/captain
        $waiter = $this->waiter
        ->with('branch:id,name', 'locations:id,name')
        ->get();
        $branches = $this->branches
        ->select('id', 'name')
        ->get();
        $cafe_locations = $this->cafe_locations
        ->select('id', 'name', 'branch_id')
        ->get();

        return response()->json([
            'waiter' => $waiter,
            'branches' => $branches,
            'cafe_locations' => $cafe_locations
        ]);
    }

    public function captain($id){
        // /admin/pos/captain/item/{id}
        $waiter = $this->waiter
        ->with('branch:id,name', 'locations:id,name')
        ->where('id', $id)
        ->first();

        return response()->json([
            'waiter' => $waiter
        ]);
    }

    public function create(CaptainOrderRequest $request){
        // /admin/pos/captain/add
        $validator = Validator::make($request->all(), [
            'email' => 'unique:waiters,email',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $captainRequest = $request->validated();
        $waiter = $this->waiter
        ->create($captainRequest);
        if ($request->locations) { 
            $waiter->locations()->attach($request->locations); 
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(CaptainOrderRequest $request, $id){
        // /admin/pos/captain/update/{id}
        $validator = Validator::make($request->all(), [
            'email' => Rule::unique('waiters')->ignore($id),
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $captainRequest = $request->validated();
        $waiter = $this->waiter
        ->where('id', $id)
        ->first();
        $waiter->update($captainRequest);
        if ($request->locations) { 
            $waiter->locations()->sync($request->locations); 
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $this->waiter
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
