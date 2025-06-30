<?php

namespace App\Http\Controllers\api\cashier\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Cashier;

class HomeController extends Controller
{
    public function __construct(private Cashier $cashier){}

    public function view(Request $request){
        $cashiers = $this->cashier
        ->where('branch_id', $request->user()->branch_id)
        ->where('cashier_active', 0)
        ->where('status', 1)
        ->get();

        return response()->json([
            'cashiers' => $cashiers
        ]);
    }

    public function active_cashier(Request $request, $id){
        $this->cashier
        ->where('id', $id)
        ->update([
            'cashier_active' => 1
        ]);

        return response()->json([
            'success' => 'You activate cashier success'
        ]);
    }
}
