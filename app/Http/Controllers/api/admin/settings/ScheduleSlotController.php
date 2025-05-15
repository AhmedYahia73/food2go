<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ScheduleSlot;

class ScheduleSlotController extends Controller
{
    public function __construct(private ScheduleSlot $time_slot){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot
        $time_slot = $this->time_slot
        ->get();

        return response()->json([
            'time_slot' => $time_slot
        ]);
    }

    public function schedule_time_slot($id){
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot/item/{id}
        $schedule_time_slot = $this->time_slot
        ->where('id', $id)
        ->first();

        return response()->json([
            'schedule_time_slot' => $schedule_time_slot
        ]);
    }

    public function status($id, Request $request){
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot/status/{id}
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

        $this->time_slot
        ->where('id', $id)
        ->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => $request->status ? 'approve' : 'banned'
        ]);
    }

    public function create(Request $request){
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot/add
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->time_slot
        ->create([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot/update/{id}
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->time_slot
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
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot/delete/{id}
        $this->time_slot
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
