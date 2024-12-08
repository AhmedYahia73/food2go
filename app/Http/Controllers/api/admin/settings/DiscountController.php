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
        // https://bcknd.food2go.online/admin/settings/discount
        $discount = $this->discount->get();

        return response()->json([
            'discounts' => $discount
        ]);
    }

    public function discount($id){
        // https://bcknd.food2go.online/admin/settings/discount/item/{id}
        $discount = $this->discount
        ->where('id', $id)
        ->first();

        return response()->json([
            'discount' => $discount
        ]);
    }

    public function create(DiscountRequest $request){
        // https://bcknd.food2go.online/admin/settings/discount/add
        // Keys
        // name, type, amount
        $discountRequest = $request->only($this->discountRequest);
        $this->discount->create($discountRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(DiscountRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/discount/update/{id}
        // Keys
        // name, type, amount
        $discountRequest = $request->only($this->discountRequest);
        $this->discount
        ->where('id', $id)
        ->update($discountRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/settings/discount/delete/{id}
        $this->discount
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }

}
