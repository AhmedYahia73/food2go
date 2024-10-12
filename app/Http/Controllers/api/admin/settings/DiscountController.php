<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\DiscountRequest;

use App\Models\Discount;

class DiscountController extends Controller
{
    public function __construct(private Discount $discount){}
    protected $discountRequest = [
        'name',
        'type',
        'amount',
    ];

    public function view(){
        $discount = $this->discount->get();

        return response()->json([
            'discounts' => $discount
        ]);
    }

    public function create(DiscountRequest $request){
        $discountRequest = $request->only($this->discountRequest);
        $this->discount->create($discountRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(DiscountRequest $request, $id){
        $discountRequest = $request->only($this->discountRequest);
        $this->discount
        ->where('id', $id)
        ->update($discountRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $this->discount
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }

}
