<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\FinantiolAcounting;

class TransferFinancialController extends Controller
{
    public function __construct(private FinantiolAcounting $financial){}

    public function view(Request $request){
        $financials = $this->financial
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'details' => $item->details,
                'balance' => $item->balance, 
                'status' => $item->status, 
                'logo' => $item->logo_link, 
                
            ];
        });

        return response()->json([
            "financials" => $financials,
        ]);
    }

    public function transfer(Request $request){
        $validator = Validator::make($request->all(), [
            'from_financial_id' => 'required|exists:finantiol_acountings,id',
            'to_financial_id' => 'required|exists:finantiol_acountings,id',
            'amount' => 'required|numeric'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $from_financial = $this->financial
        ->where('id', $request->from_financial_id)
        ->first();
        if($from_financial->balance < $request->amount){
            return response()->json([
                'errors' => "Balance at " . $from_financial->name . " not enough",
            ], 400);
        }
        $to_financial = $this->financial
        ->where('id', $request->to_financial_id)
        ->first();
        $from_financial->balance -= $request->amount;
        $to_financial->balance += $request->amount;
        $from_financial->save();
        $to_financial->save();

        return response()->json([
            'success' => 'You transfere balance success'
        ]);
    }
}
