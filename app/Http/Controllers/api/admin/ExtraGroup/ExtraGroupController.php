<?php

namespace App\Http\Controllers\api\admin\ExtraGroup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ExtraGroup;

class ExtraGroupController extends Controller
{
    public function __construct(private ExtraGroup $extra_group){}

    public function view(){
        // https://bcknd.food2go.online/admin/extra_group
        $extra_group = $this->extra_group
        ->get();

        return response()->json([
            'extra_group' => $extra_group
        ]);
    }

    public function group($id){
        // https://bcknd.food2go.online/admin/extra_group/item/{id}
        $extra_group = $this->extra_group
        ->where('id', $id)
        ->first();

        return response()->json([
            'extra_group' => $extra_group
        ]);
    } 

    public function create(Request $request){
        // https://bcknd.food2go.online/admin/extra_group/add
        //Key
        // name, pricing, group_id
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'pricing' => 'required|numeric',
            'group_id' => 'required|exists:groups,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->extra_group
        ->create([
            'name' => $request->name,
            'pricing' => $request->pricing,
            'group_id' => $request->group_id,
        ]);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/admin/extra_group/update/{id}
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'pricing' => 'required|numeric',
            'group_id' => 'required|exists:groups,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->extra_group
        ->where('id', $id)
        ->update([
            'name' => $request->name,
            'pricing' => $request->pricing,
            'group_id' => $request->group_id,
        ]);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/extra_group/delete/{id}
        $this->extra_group
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
