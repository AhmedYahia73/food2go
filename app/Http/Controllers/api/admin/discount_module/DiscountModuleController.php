<?php

namespace App\Http\Controllers\api\admin\discount_module;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DiscountModuleBranch;
use App\Models\DiscountModule;

class DiscountModuleController extends Controller
{
    public function __construct(private DiscountModuleBranch $discount_module_branch,
    private DiscountModule $discount_module){}

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

        return response()->json([
            "discounts" => $discounts
        ]);
    }

    public function discount_item(Request $request, $id){
        
    }

    public function create(Request $request){
        
    }

    public function modify(Request $request, $id){
        
    }

    public function delete(Request $request, $id){
        
    }
}
