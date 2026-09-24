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
        "module"
    ];

    public function view(){
        // https://bcknd.food2go.online/admin/settings/discount
        $discount = $this->discount->withoutGlobalScope('active_period')->get();

        return response()->json([
            'discounts' => $discount
        ]);
    }

    public function lists($id){
        $products = Product::
        whereDoesntHave("discounts", function($query) use($id){
            $query->withoutGlobalScope('active_period')->where("discounts.id", $id);
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
            $query->withoutGlobalScope('active_period')->where("discounts.id", $id);
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
            $query->withoutGlobalScope('active_period')->where("discounts.id", $id);
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
            $query->withoutGlobalScope('active_period')->where("discounts.id", $id);
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

        // 1. Unset discount_id from all products previously associated with this discount
        $oldProducts = Product::where("discount_id", $request->discount_id)->get();
        foreach ($oldProducts as $p) {
            $p->discount_id = null;
            $p->save(); // Eloquent save triggers LogChanges trait for desktop sync
        }

        // 2. Set discount_id on newly selected categories/products
        $categoryIds = $request->categories ?? [];
        $productIds = $request->products ?? [];
        if (!empty($categoryIds) || !empty($productIds)) {
            $targetProducts = Product::where(function($q) use ($categoryIds, $productIds) {
                if (!empty($categoryIds)) {
                    $q->whereIn("category_id", $categoryIds)
                      ->orWhereIn("sub_category_id", $categoryIds);
                }
                if (!empty($productIds)) {
                    $q->orWhereIn("id", $productIds);
                }
            })->get();

            foreach ($targetProducts as $p) {
                $p->discount_id = $request->discount_id;
                $p->save(); // Eloquent save triggers LogChanges trait for desktop sync
            }
        }

        $discount = Discount::withoutGlobalScope('active_period')
            ->where("id", $request->discount_id)
            ->first();

        if ($discount) {
            $discount->products()->sync($request->products ?? []);
            $discount->categories()->sync($request->categories ?? []);
            $discount->touch();
        }
        
        return response()->json([
            "success" => "You update data success", 
        ]);
    }

    public function discount($id){
        // https://bcknd.food2go.online/admin/settings/discount/item/{id}
        $discount = $this->discount
            ->withoutGlobalScope('active_period')
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
        $discount = $this->discount->withoutGlobalScope('active_period')->find($id);
        if ($discount) {
            $discount->update($discountRequest);
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/settings/discount/delete/{id}
        $discount = $this->discount->withoutGlobalScope('active_period')->find($id);
        if ($discount) {
            // Unset discount_id on all products referencing this discount
            $affectedProducts = Product::where('discount_id', $id)->get();
            foreach ($affectedProducts as $product) {
                $product->discount_id = null;
                $product->save(); // triggers LogChanges
            }
            $discount->products()->detach();
            $discount->categories()->detach();
            $discount->delete(); // triggers LogChanges delete
        }

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
