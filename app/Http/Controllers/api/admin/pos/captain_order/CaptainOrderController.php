<?php

namespace App\Http\Controllers\api\admin\pos\captain_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\pos\CaptainOrderRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\trait\image;

use App\Models\CaptainOrder;
use App\Models\CafeLocation;
use App\Models\Branch;

class CaptainOrderController extends Controller
{
    public function __construct(private Branch $branches, 
    private CaptainOrder $captain_order, private CafeLocation $cafe_locations){}
    use image;

    public function view(){
        // /admin/pos/captain
        $captain_order = $this->captain_order
        ->with('branch:id,name', 'locations:id,name')
        ->get();
        $branches = $this->branches
        ->select('id', 'name')
        ->get();
        $cafe_locations = $this->cafe_locations
        ->select('id', 'name', 'branch_id')
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
        ->with('branch:id,name', 'locations:id,name')
        ->where('id', $id)
        ->first();

        return response()->json([
            'captain_order' => $captain_order
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $captain_order = $this->captain_order 
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(CaptainOrderRequest $request){
        // /admin/pos/captain/add
        $validator = Validator::make($request->all(), [
            'user_name' => 'unique:captain_orders,user_name',
            'password' => ['required'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 
        $captainRequest = $request->validated();
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/captain/image');
            $captainRequest['image'] = $imag_path;
        }
        $captainRequest['password'] = $request->password;
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
            'user_name' => Rule::unique('captain_orders')->ignore($id),
            'phone' => Rule::unique('captain_orders')->ignore($id),
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $captainRequest = $request->validated();
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/captain/image');
            $captainRequest['image'] = $imag_path;
        }
        if(!empty($request->password)){
            $captainRequest['password'] = bcrypt($request->password);
        }
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
