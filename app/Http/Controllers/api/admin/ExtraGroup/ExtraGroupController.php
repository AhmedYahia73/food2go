<?php

namespace App\Http\Controllers\api\admin\ExtraGroup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ExtraGroup;
use App\Models\TranslationTbl;
use App\Models\Translation;

class ExtraGroupController extends Controller
{
    public function __construct(private ExtraGroup $extra_group, private Translation $translations,
    private TranslationTbl $translation_tbl){}

    public function view($id){
        // https://bcknd.food2go.online/admin/extra_group/group/{id}
        $extra_group = $this->extra_group
        ->where('group_id', $id)
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
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $extra_names = [];
        foreach ($translations as $item) {
             $extra_name = $this->translation_tbl
             ->where('locale', $item->name)
             ->where('key', $extra_group->name)
             ->first();
            $extra_names[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'extra_name' => $extra_name->value ?? null,
            ];
        }

        return response()->json([
            'extra_group' => $extra_group,
            'extra_names' => $extra_names,
        ]);
    } 

    public function create(Request $request){
        // https://bcknd.food2go.online/admin/extra_group/add
        //Key
        // extra_names[tranlation_name, extra_name], pricing, group_id, min, max
        $validator = Validator::make($request->all(), [
            'extra_names' => 'required',
            'extra_names.*.tranlation_name' => 'required',
            'extra_names.*.extra_name' => 'required',
            'pricing' => 'required|numeric',
            'group_id' => 'required|exists:groups,id',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $default = $request->extra_names[0];
        $extra_group = $this->extra_group
        ->create([
            'name' => $default['extra_name'],
            'pricing' => $request->pricing,
            'group_id' => $request->group_id,
            'min' => $request->min,
            'max' => $request->max,
        ]);
        foreach ($request->extra_names as $item) {
            if (!empty($item['extra_name'])) {
                $extra_group->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['extra_name'],
                    'value' => $item['extra_name']
                ]);
            }
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // https://bcknd.food2go.online/admin/extra_group/update/{id}
        //Key
        // extra_names[tranlation_name, extra_name], pricing, group_id, min, max
        $validator = Validator::make($request->all(), [
            'extra_names' => 'required',
            'extra_names.*.tranlation_name' => 'required',
            'extra_names.*.extra_name' => 'required',
            'pricing' => 'required|numeric',
            'group_id' => 'required|exists:groups,id',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $default = $request->extra_names[0];
        $extra_group = $this->extra_group
        ->where('id', $id)
        ->first();
        $extra_group->update([
            'name' => $default['extra_name'],
            'pricing' => $request->pricing,
            'group_id' => $request->group_id,
            'min' => $request->min,
            'max' => $request->max,
        ]);
        $extra_group->translations()->delete();
        foreach ($request->extra_names as $item) {
            if (!empty($item['extra_name'])) {
                $extra_group->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['extra_name'],
                    'value' => $item['extra_name']
                ]);
            }
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/extra_group/delete/{id}
        $extra_group = $this->extra_group
        ->where('id', $id)
        ->first();
        $extra_group->translations()->delete();
        $extra_group->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
