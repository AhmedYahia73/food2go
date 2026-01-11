<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validation = Validator::make($request->all(), [
            'cashier_id' => 'exists:cashiers,id',
            'cashier_man_id' => 'exists:cashier_men,id',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $gaps = CashierGap::
        with("cashier", "cashier_man");
        if($request->cashier_id){
            $gaps = $gaps
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->cashier_man_id){
            $gaps = $gaps
            ->where("cashier_man_id", $request->cashier_man_id);
        }
        $gaps = $gaps
        ->get()
        ->map(function($item){
            $date = $item?->shift?->created_at ?? $item->created_at;
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "cashier_id" => $item->cashier_id,
                "cashier_man_id" => $item->cashier_man_id,
                "cashier" => $item?->cashier?->name,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $date->format("Y-m-d"),
            ];
        });

        return response()->json([
            "gaps" => $gaps
        ]);
    }
}
