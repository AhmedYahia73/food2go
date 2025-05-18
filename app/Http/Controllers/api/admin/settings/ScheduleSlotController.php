<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ScheduleSlot;
use App\Models\Translation;
use App\Models\TranslationTbl;

class ScheduleSlotController extends Controller
{
    public function __construct(private ScheduleSlot $time_slot,
    private Translation $translations, private TranslationTbl $translation_tbl){}

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
        $schedule_time_slot_item = $this->time_slot
        ->where('id', $id)
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $slot_names = [];
        foreach ($translations as $item) {
            $schedule_time_slot = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $schedule_time_slot_item->name)
            ->first();
           $slot_names[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'schedule_time_slot' => $schedule_time_slot->value ?? null,
           ];
            // $filePath = base_path("lang/{$item->name}/messages.php");
            // if (File::exists($filePath)) {
            //     $translation_file = require $filePath;
            //     $category_names[] = [
            //         'id' => $item->id,
            //         'lang' => $item->name,
            //         'name' => $translation_file[$category->name] ?? null
            //     ];
            // }
        }

        return response()->json([
            'schedule_time_slot' => $schedule_time_slot_item,
            'slot_names' => $slot_names,
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
                'errors' => $validator->errors(),
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
        // status
        // slot_names[{name, tranlation_id, tranlation_name}]
        $validator = Validator::make($request->all(), [
            'slot_names' => 'required',
            'slot_names.*.name' => 'required',
            'slot_names.*.tranlation_id' => 'required|exists:translations,id',
            'slot_names.*.tranlation_name' => 'required|exists:translations,name',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $default = $request->slot_names[0];

        $time_slot = $this->time_slot
        ->create([
            'name' => $default['name'],
            'status' => $request->status,
        ]);
        foreach ($request->slot_names as $item) {
            if (!empty($item['name'])) {
                $time_slot->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['name'],
                    'value' => $item['name']
                ]);
            }
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/admin/settings/schedule_time_slot/update/{id}
        // Key
        // status
        // slot_names[{name, tranlation_id, tranlation_name}]
        $validator = Validator::make($request->all(), [
            'slot_names' => 'required',
            'slot_names.*.name' => 'required',
            'slot_names.*.tranlation_id' => 'required|exists:translations,id',
            'slot_names.*.tranlation_name' => 'required|exists:translations,name',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $default = $request->slot_names[0];
        $time_slot = $this->time_slot
        ->where('id', $id)
        ->first();
        $time_slot->update([
            'name' => $default['name'],
            'status' => $request->status,
        ]);
        $time_slot->translations()->delete();
        foreach ($request->slot_names as $item) {
            if (!empty($item['name'])) {
                $time_slot->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['name'],
                    'value' => $item['name']
                ]);
            }
        }

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
