<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\FinantiolAcounting;
use App\Models\FinancialHistory;

class TransferFinancialController extends Controller
{
    public function __construct(private FinantiolAcounting $financial,
    private FinancialHistory $financial_history){}

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
        $this->financial_history
        ->create([
            'from_financial_id' => $request->from_financial_id,
            'to_financial_id' => $request->to_financial_id,
            'admin_id' => $request->user()->id,
            'amount' => $request->amount,
        ]);

        return response()->json([
            'success' => 'You transfere balance success'
        ]);
    }

    public function history(Request $request){
        $financial_history = $this->financial_history
        ->with(['from_financial', 'to_financial', 'admin'])
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'amount' => $item->amount,
                'from_financial' => $item?->from_financial?->name,
                'to_financial' => $item?->to_financial?->name,
                'amount' => $item->amount,
                'admin' => $item?->admin?->name,
                "date" => $item->created_at->format('Y-m-d'),
                "time" => $item->created_at->format('h:i A'),
            ];
        });

        return response()->json([
            "financial_history" => $financial_history
        ]);
    }
}
