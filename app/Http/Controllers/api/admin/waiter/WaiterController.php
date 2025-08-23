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
        // /admin/waitern
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

    public function waiter($id){
        // /admin/waitern/item/{id}
        $waiter = $this->waiter
        ->with('branch:id,name', 'locations:id,name')
        ->where('id', $id)
        ->first();

        return response()->json([
            'waiter' => $waiter
        ]);
    }

    public function status(Request $request, $id){
        // /admin/waitern/status/{id}
        $validator = Validator::make($request->all(), [ 
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $waiter = $this->waiter 
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        // /admin/waitern/add
        $validator = Validator::make($request->all(), [
            'branch_id' => ['required', 'exists:branches,id'],
            'user_name' => ['required', 'unique:waiters,user_name'],
            'password' => ['required'],
            'locations.*' => ['required', 'exists:cafe_locations,id'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $waiterRequest = $validator->validated();
        $waiter = $this->waiter
        ->create($waiterRequest);
        if ($request->locations) { 
            $waiter->locations()->attach($request->locations); 
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // /admin/pos/waiter/update/{id}
        $validator = Validator::make($request->all(), [
            'branch_id' => ['required', 'exists:branches,id'],
            'user_name' => ['required', 'unique:waiters,user_name,' . $id],  
            'status' => ['required', 'boolean'],
            'locations.*' => ['required', 'exists:cafe_locations,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $waiterRequest = $validator->validated();
        $waiter = $this->waiter
        ->where('id', $id)
        ->first();
        if(!empty($request->password)){
            $waiterRequest['password'] = bcrypt($request->password);
        }
        $waiter->update($waiterRequest);
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
