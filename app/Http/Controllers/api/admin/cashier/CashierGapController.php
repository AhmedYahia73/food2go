<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\CashierGap;
use App\Models\Cashier;
use App\Models\CashierMan;

class CashierGapController extends Controller
{
    public function lists(Request $request){
        $cashiers = Cashier::
        select("id", "name")
        ->get();
        $cashier_men = CashierMan::
        select("id", "user_name")
        ->get();

        return response()->json([
            "cashiers" => $cashiers,
            "cashier_men" => $cashier_men,
        ]);
    }

    public function cashier_gap(Request $request){
        $gaps = CashierGap::
        with("cashier", "cashier_man")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "cashier_id" => $item->cashier_id,
                "cashier_man_id" => $item->cashier_man_id,
                "cashier" => $item?->cashier?->name,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $item?->shift?->created_at ?? $item->created_at,
            ];
        });

        return response()->json([
            "gaps" => $gaps
        ]);
    }
}
