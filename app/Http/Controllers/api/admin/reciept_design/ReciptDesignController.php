<?php

namespace App\Http\Controllers\api\admin\reciept_design;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ReceiptDesign;

class ReciptDesignController extends Controller
{
    public function __construct(private ReceiptDesign $reciept){}

    public function view(){
        $reciept = $this->reciept
        ->first();

        return response()->json([
            'logo' => $reciept->logo ?? 1,
            'name' => $reciept->name ?? 1,
            'address' => $reciept->address ?? 1,
            'branch' => $reciept->branch ?? 1,
            'phone' => $reciept->phone ?? 1,
            'cashier_name' => $reciept->cashier_name ?? 1,
            'footer' => $reciept->footer ?? 1,
            'taxes' => $reciept->taxes ?? 1,
            'services' => $reciept->services ?? 1,
            'table_num' => $reciept->table_num ?? 1,
            'preparation_num' => $reciept->preparation_num ?? 1,
        ]);
    }

    public function update(Request $request){
        $reciept = $this->reciept
        ->first();
        if(empty($reciept)){
            $this->reciept
            ->create([
                'logo' => $request->logo ?? 1,
                'name' => $request->name ?? 1,
                'address' => $request->address ?? 1,
                'branch' => $request->branch ?? 1,
                'phone' => $request->phone ?? 1,
                'cashier_name' => $request->cashier_name ?? 1,
                'footer' => $request->footer ?? 1,
                'taxes' => $request->taxes ?? 1,
                'services' => $request->services ?? 1,
                'table_num' => $request->table_num ?? 1,
                'preparation_num' => $request->preparation_num ?? 1,
            ]);
        }
        else{
            $reciept->update([
                'logo' => $request->logo ?? $reciept->logo,
                'name' => $request->name ?? $reciept->name,
                'address' => $request->address ?? $reciept->address,
                'branch' => $request->branch ?? $reciept->branch,
                'phone' => $request->phone ?? $reciept->phone,
                'cashier_name' => $request->cashier_name ?? $reciept->cashier_name,
                'footer' => $request->footer ?? $reciept->footer,
                'taxes' => $request->taxes ?? $reciept->taxes,
                'services' => $request->services ?? $reciept->services,
                'table_num' => $request->table_num ?? $reciept->table_num,
                'preparation_num' => $request->preparation_num ?? $reciept->preparation_num,
            ]);
        }

        return response()->json([
            "success" => "You update success"
        ]);
    }
}
