<?php

namespace App\Http\Controllers\api\admin\Group;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Group;

class GroupController extends Controller
{
    public function __construct(private Group $groups){}

    public function view(){
        // https://bcknd.food2go.online/admin/group
        $groups = $this->groups
        ->get();

        return response()->json([
            'groups' => $groups
        ]);
    }

    public function group($id){
        // https://bcknd.food2go.online/admin/group/item/{id}
        $group = $this->groups
        ->where('id', $id)
        ->first();

        return response()->json([
            'group' => $group
        ]);
    }

    public function status($id, Request $request){
        // https://bcknd.food2go.online/admin/group/status/{id}
        // Key
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->groups
        ->where('id', $id)
        ->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => $request->status ? 'approve' : 'banned'
        ]);
    }

    public function create(Request $request){
        // https://bcknd.food2go.online/admin/group/add
        // Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->groups
        ->create([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/admin/group/update/{id}
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->groups
        ->where('id', $id)
        ->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/group/delete/{id}
        $this->groups
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
