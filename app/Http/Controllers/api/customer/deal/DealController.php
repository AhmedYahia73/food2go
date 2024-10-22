<?php

namespace App\Http\Controllers\api\customer\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Deal;
use App\Models\DealTimes;

class DealController extends Controller
{
    public function __construct(private Deal $deals, private DealTimes $deal_times){}

    public function index(){
        $today = Carbon::now()->format('l');
        $deals = $this->deals
        ->with('times')
        ->whereHas('times', function($query) use($today) {
            $query->where('day', $today)
            ->where('from', '<=', now()->format('H:i:s'))
            ->where('to', '>=', now()->format('H:i:s'));
        })
        ->get();
        
        return response()->json([
            'deals' => $deals,
        ]);
    }
}
