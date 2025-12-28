<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Cashier;
use App\Models\Branch;
use App\Models\Translation;
use App\Models\TranslationTbl;

class CashierController extends Controller
{
    public function __construct(private Cashier $cashier,
    private Branch $branches, private Translation $translations
    , private TranslationTbl $translation_tbl){}

    public function view(Request $request){
        // /admin/cashier
        $cashier = $this->cashier
        ->with(['branch:id,name'])
        ->get()
        ->map(function($item){
            $cashier_man = $item->cashier_man->sortByDesc("id")->first();
            return [
                "id" => $item->id,
                "name" => $item->name,
                "cashier_active" => $item->cashier_active,
                "status" => $item->status,
                "branch" => $item->branch,
                "cashier_man" => [
                    "id" => $cashier_man?->id,
                    "user_name" => $cashier_man?->user_name,
                    "image_link" => $cashier_man?->image_link,
                ],
            ];
        });
        $branches = $this->branches
        ->select('id', 'name')
        ->where('status', 1)
        ->get();

        return response()->json([
            'cashiers' => $cashier,
            'branches' => $branches,
        ]);
    }

    public function status(Request $request, $id){
        // admin/cashier/status/{id}
        // Keys
        // status
        $validation = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashier = $this->cashier
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]); 

        return response()->json([
            'success' => $request->status ? 'active' : 'banned',
        ]);
    }
    
    public function cashier(Request $request, $id){
        // /admin/cashier/item/{id}
        $cashier = $this->cashier 
        ->where('id', $id)
        ->with(['branch:id,name'])
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $cashier_names = [];
        foreach ($translations as $item) {
            $cashier_name = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $cashier->name)
            ->first();
           $cashier_names[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'cashier_name' => $cashier_name->value ?? null,
           ]; 
        }

        return response()->json([
            'cashier' => $cashier,
            "cashier_names" => $cashier_names
        ]);
    }

    public function create(Request $request){
        // admin/cashier/add
        // Keys
        // name, branch_id, status
        $validation = Validator::make($request->all(), [
            'cashier_names' => 'required|array',
            'cashier_names.*.tranlation_name' => 'required',
            'cashier_names.*.tranlation_id' => 'required',
            'cashier_names.*.name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
            "print_type" => 'required|in:usb,network',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $validation->validated();
        $cashier_names = $request->cashier_names;
        $default = $cashier_names[0]["name"];
        $cashier = $this->cashier
        ->create([
            "name" => $default,
            "branch_id" => $request->branch_id,
            "status" => $request->status,
        ]);
        foreach ($cashier_names as $item) {
            if (!empty($item['name'])) {
                $cashier->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default,
                    'value' => $item['name']
                ]);
            }
        }

        return response()->json([
            'success' => $cashier,
        ]);
    }

    public function modify(Request $request, $id){
        // admin/cashier/update/{id}
        // Keys
        // name, branch_id, status
        $validation = Validator::make($request->all(), [
            'cashier_names' => 'required|array',
            'cashier_names.*.tranlation_name' => 'required',
            'cashier_names.*.tranlation_id' => 'required',
            'cashier_names.*.name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            "print_type" => 'required|in:usb,network',
            'status' => 'required|boolean',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $validation->validated();
        $cashier_names = $request->cashier_names;
        $default = $cashier_names[0]["name"];
        $cashier = $this->cashier
        ->where('id', $id)
        ->first();
        if (empty($cashier)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        $cashier->update([
            "name" => $default,
            "branch_id" => $request->branch_id,
            "status" => $request->status,
        ]);
        $cashier->translations()->delete();
        foreach ($cashier_names as $item) {
            if (!empty($item['name'])) {
                $cashier->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default,
                    'value' => $item['name']
                ]);
            }
        }
        return response()->json([
            'success' => $cashier,
        ]);
    }

    public function delete(Request $request, $id){
        // admin/cashier/delete/{id}   
        $cashier = $this->cashier
        ->where('id', $id)
        ->first();
        if (empty($cashier)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        $cashier->delete();

        return response()->json([
            'success' => 'You delete cashier success'
        ], 200);
    }
}
