<?php

namespace App\Http\Controllers\api\admin\discount_module;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        return response()->json([
            "discounts" => $discounts,
            "branches" => $branches,
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
        $discount_module = $this->discount_module
        ->create([
            "discount" => $request->discount,
            "status" => $request->status,
        ]);

    }

    public function modify(Request $request, $id){
        
    }

    public function delete(Request $request, $id){
        
    }
}
