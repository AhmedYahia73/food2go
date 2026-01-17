<?php

namespace App\Http\Controllers\api\admin\tax_module;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\TaxModuleBranch;
use App\Models\TaxModule;
use App\Models\Branch;

class TaxModuleController extends Controller
{
    public function __construct(private TaxModuleBranch $tax_module_branch,
    private TaxModule $tax_module, private Branch $branches){}

    public function view(Request $request){
        $taxs = $this->tax_module
        ->with('module.branch')
        ->get()
        ->map(function($item){
            $modules = $item->module
            ->map(function($element){
                return [
                    "type" => $element->type,
                    "module" => $element->module,
                    "branch" => $element?->branch?->name,
                ];
            });
            return [
                "id" => $item->id,
                "tax" => $item->tax,
                "status" => $item->status,
                "modules" => $modules,
            ];
        });
        $branches = $this->branches
        ->get()
        ->select("id", "name");
        $modules = [
            "take_away",
            "dine_in",
            "delivery",
        ];
        $type = [
            'all',
            'app',
            'web',
        ];

        return response()->json([
            "taxs" => $taxs,
            "branches" => $branches,
            "modules" => $modules,
            "type" => $type,
        ]);
    }

    public function tax_item(Request $request, $id){
        $tax = $this->tax_module
        ->where("id", $id)
        ->with('module.branch')
        ->first();
        $modules = $tax?->module
        ->map(function($element){
            return [
                "module" => $element->module,
                "branch" => $element?->branch?->name,
                "type" => $element->type,
            ];
        });

        return response()->json([
            "id" => $tax?->id,
            "tax" => $tax?->tax,
            "status" => $tax?->status,
            "modules" => $modules,
        ]);
    }

    public function create(Request $request){ 
        $validator = Validator::make($request->all(), [
            'tax' => 'required|numeric',
            'status' => 'required|boolean',
            'branch_modules' => 'required|array',
            'branch_modules.*.type' => 'required|in:all,app,web',
            'branch_modules.*.branch_id' => 'required|exists:branches,id',
            'branch_modules.*.module' => 'required|in:take_away,dine_in,delivery',
        ]); 
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $tax_module = $this->tax_module
        ->create([
            "tax" => $request->tax,
            "status" => $request->status,
        ]);
        foreach ($request->branch_modules as $item) {
            $this->tax_module_branch
            ->create([
                'tax_module_id' => $tax_module->id,
                'branch_id' => $item['branch_id'],
                'module' => $item['module'],
                'type' => $item['type'],
            ]);
        }

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'tax' => 'required|numeric',
            'status' => 'required|boolean',
            'branch_modules' => 'required|array',
            'branch_modules.*.type' => 'required|in:all,app,web',
            'branch_modules.*.branch_id' => 'required|exists:branches,id',
            'branch_modules.*.module' => 'required|in:take_away,dine_in,delivery',
        ]); 
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $tax_module = $this->tax_module
        ->where("id", $id)
        ->update([
            "tax" => $request->tax,
            "status" => $request->status,
        ]);
        $this->tax_module_branch
        ->where("tax_module_id", $id)
        ->delete();
        foreach ($request->branch_modules as $item) {
            $this->tax_module_branch
            ->create([
                'tax_module_id' => $id,
                'branch_id' => $item['branch_id'],
                'module' => $item['module'],
                'type' => $item['type'],
            ]);
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->tax_module
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
