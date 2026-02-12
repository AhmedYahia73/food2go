<?php

namespace App\Http\Controllers\api\admin\product_offers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ProductOffer;
use App\Models\Product;

class ProductOfferController extends Controller
{ 
    public function index()
    { 
        $offers = ProductOffer::
        with("products:id,name")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "module" => $item->module,
                "start_date" => $item->start_date,
                "end_date" => $item->end_date,
                "discount" => $item->discount,
                "time_from" => $item->time_from,
                "time_to" => $item->time_to,
                "delay" => $item->delay,
                "days" => $item->days,
                "products" => $item?->products->map(function($element){
                    return [
                        "id" => $element->id,
                        "name" => $element->name,
                    ];
                }),
            ];
        });

        return response()->json([
            "offers" => $offers, 
        ]);
    }

    public function lists(){ 
        $days = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ];
        $modules = [
            'take_away',
            'dine_in',
            'delivery'
        ];
        $products = Product::
        where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "days" => $days,
            "modules" => $modules,
            "products" => $products,
        ]);
    }
 
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'module' => ['required', 'array'],
            'module.*' => ['required', 'in:take_away,dine_in,delivery'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'discount' => ['required', 'numeric'],
            'time_from' => ['required', 'date_format:H:i:s'],
            'time_to' => ['required', 'date_format:H:i:s'],
            'days' => ['sometimes', 'array'],
            'days.*' => ['sometimes', 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
            'delay' => ['required'],
            'products' => ['required', 'array'],
            'products.*' => ['required', 'exists:products,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $offerRequest = $validator->validated();
        $offer = ProductOffer::create($offerRequest);
        $offer->products()->attach($request->products);
 
        return response()->json([
            "success" => "You add data success",
        ]);
    }
 
    public function show(string $id)
    {
        $offer = ProductOffer::
        with("products:id,name")
        ->where("id", $id)
        ->first();
 
        return response()->json([
            "offer" => $offer,
        ]);
    }
 
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'module' => ['required', 'array'],
            'module.*' => ['required', 'in:take_away,dine_in,delivery'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'discount' => ['required', 'numeric'],
            'time_from' => ['required', 'date_format:H:i:s'],
            'time_to' => ['required', 'date_format:H:i:s'],
            'days' => ['sometimes', 'array'],
            'days.*' => ['sometimes', 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
            'delay' => ['required'],
            'products' => ['required', 'array'],
            'products.*' => ['required', 'exists:products,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $offerRequest = $validator->validated();
        $offer = ProductOffer::
        findOrFail($id);
        $offer->update($offerRequest);
        $offer->products()->attach($request->products);
 
        return response()->json([
            "success" => "You update data success",
        ]);
    }
 
    public function destroy(string $id)
    {
        ProductOffer::
        where("id", $id)
        ->delete();
 
        return response()->json([
            "success" => "You delete data success",
        ]);
    }
}
