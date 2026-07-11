<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\admin\settings\DiscountRequest;

use App\Models\Discount;
use App\Models\Product;
use App\Models\Category;

class DiscountController extends Controller
{
    public function __construct(private Discount $discount){}
    protected $discountRequest = [
        'name',
        'type',
        'amount',
        "start_date",
        "end_date",
    ];

    public function view(){
        // https://bcknd.food2go.online/admin/settings/discount
        $discount = $this->discount->get();

        return response()->json([
            'discounts' => $discount
        ]);
    }

    public function lists($id){
        $products = Product::
        whereDoesntHave("discounts", function($query) use($id){
            $query->where("discounts.id", $id);
        })
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $categories = Category::
        whereDoesntHave("discounts", function($query) use($id){
            $query->where("discounts.id", $id);
        })
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "products" => $products,
            "categories" => $categories,
        ]);
    }

    public function show_products(Request $request, $id){
        // discounts 
        $products = Product::
        whereHas("discounts", function($query) use($id){
            $query->where("discounts.id", $id);
        })
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $categories = Category::
        whereHas("discounts", function($query) use($id){
            $query->where("discounts.id", $id);
        })
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "products" => $products,
            "categories" => $categories,
        ]);
    }

    public function discount_product(Request $request){
        // discounts 
        $validation = Validator::make($request->all(), [
            'categories' => 'array',
            'products' => 'array',
            'categories.*' => 'exists:categories,id',
            'products.*' => 'exists:products,id',
            'discount_id' => 'required|exists:discounts,id',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }

        $products = Product::
        where("discount_id", $request->discount_id)
        ->update([
            "discount_id" => null
        ]);
        $products = Product::
        whereIn("category_id", $request->categories)
        ->orWhereIn("sub_category_id", $request->categories)
        ->orWhereIn("id", $request->products)
        ->update([
            "discount_id" => $request->discount_id
        ]);
         $discount = Discount::
         where("id", $request->discount_id)
         ->first();
         $discount->products()->sync($request->products ?? []);
         $discount->categories()->sync($request->categories ?? []);
        
        return response()->json([
            "success" => "You update data success", 
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
        // name, type, amount, start_date, end_date, module => [all,pos,web,app]

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
