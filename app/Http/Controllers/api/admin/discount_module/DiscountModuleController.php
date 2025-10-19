<?php

namespace App\Http\Controllers\api\admin\discount_module;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\DiscountModuleBranch;
use App\Models\DiscountModule;
use App\Models\Branch;

class DiscountModuleController extends Controller
{
    public function __construct(private DiscountModuleBranch $discount_module_branch,
    private DiscountModule $discount_module, private Branch $branches){}

    public function view(Request $request){
        $discounts = $this->discount_module
        ->with('module.branch')
        ->get()
        ->map(function($item){
            $modules = $item->module
            ->map(function($element){
                return [
                    "module" => $element->module,
                    "branch" => $element?->branch?->name,
                ];
            });
            return [
                "id" => $item->id,
                "discount" => $item->discount,
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

        return response()->json([
            "discounts" => $discounts,
            "branches" => $branches,
            "modules" => $modules,
        ]);
    }

    public function discount_item(Request $request, $id){
        $discount = $this->discount_module
        ->where("id", $id)
        ->with('module.branch')
        ->first();
        $modules = $discount?->module
        ->map(function($element){
            return [
                "module" => $element->module,
                "branch" => $element?->branch?->name,
            ];
        });

        return response()->json([
            "id" => $discount?->id,
            "discount" => $discount?->discount,
            "status" => $discount?->status,
            "modules" => $modules,
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'discount' => 'required|numeric',
            'status' => 'required|boolean',
            'branch_modules' => 'required|array',
            'branch_modules.*.branch_id' => 'required|exists:branches,id',
            'branch_modules.*.module' => 'required|in:take_away,dine_in,delivery',
        ]); 
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $discount_module = $this->discount_module
        ->create([
            "discount" => $request->discount,
            "status" => $request->status,
        ]);
        foreach ($request->branch_modules as $item) {
            $this->discount_module_branch
            ->create([
                'discount_module_id' => $discount_module->id,
                'branch_id' => $item['branch_id'],
                'module' => $item['module'],
            ]);
        }

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'discount' => 'required|numeric',
            'status' => 'required|boolean',
            'branch_modules' => 'required|array',
            'branch_modules.*.branch_id' => 'required|exists:branches,id',
            'branch_modules.*.module' => 'required|in:take_away,dine_in,delivery',
        ]); 
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $discount_module = $this->discount_module
        ->where("id", $id)
        ->update([
            "discount" => $request->discount,
            "status" => $request->status,
        ]);
        $this->discount_module_branch
        ->where("discount_module_id", $discount_module->id)
        ->delete();
        foreach ($request->branch_modules as $item) {
            $this->discount_module_branch
            ->create([
                'discount_module_id' => $discount_module->id,
                'branch_id' => $item['branch_id'],
                'module' => $item['module'],
            ]);
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->discount_module
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
