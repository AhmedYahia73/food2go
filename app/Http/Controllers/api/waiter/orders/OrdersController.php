<?php

namespace App\Http\Controllers\api\waiter\orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\KitchenOrder;
use App\Models\OrderCart;
use App\Models\Kitchen;

class OrdersController extends Controller
{
    public function __construct(private OrderCart $order_carts,
    private KitchenOrder $kitchen_order, private Kitchen $kitchen){}

    public function view(Request $request){
        $locations = $request->user()?->locations?->pluck('id') ?? collect([]);
        $orders = $this->order_carts
        ->where('prepration_status', 'done')
        ->whereHas('table', function($query) use($locations){
            $query->where('location_id', $locations);
        })
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'notes' => $item->notes,
                'table' => $item?->table?->table_number,
                'location' => $item?->table?->location?->name, 
            ];
        });

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function order(Request $request, $id){
        $orders = $this->order_carts
        ->where('id', $id)
        ->first();
        $product = $orders->cart[0]->product;
        $product = collect($product)?->pluck('product');
        $products_id = collect($product)?->pluck('id');
        $categories_id = [collect($product)?->pluck('sub_category_id'),
						 collect($product)?->pluck('category_id')];
		$categories_id = collect($categories_id)->flatten();
        $kitchen = $this->kitchen
        ->select('name', 'type')
        ->where('branch_id', $request->user()->branch_id)
        ->whereHas('products', function($query) use($products_id){
            $query->whereIn('products.id', $products_id);
        })
        ->orWhere('branch_id', $request->user()->branch_id)
        ->whereHas('category', function($query) use($categories_id){
            $query->whereIn('categories.id', $categories_id);
        })
        ->get();
        $cart = collect($orders->cart)
        ->map(function($item){ 
            $extras = collect($item->extras);
            $extras = $extras->select('id', 'name');
            
            $addons = collect($item->addons);
            $addons = $addons->map(function($element){
                $addon = collect($element->addon); 
                return [
                    'addon' => ['id' => $addon['id'], 'name' => $addon['name']],
                    'count' => $element->count,
                ];
            });
             
            $excludes = collect($item->excludes);
            $excludes = $excludes->select('id', 'name');
             
            $product = collect($item->product);
            $product = $product->map(function($element){
                $product = collect($element->product);
                return [
                    'product' => ['id' => $product['id'], 'name' => $product['name']],
                    'count' => $element->count,
                ];
            });
             
            $variations = collect($item->variations);
            $variations = $variations->map(function($element){
                $variations = collect($element->variation); 
                return [
                    'variations' => ['id' => $variations['id'], 'name' => $variations['name']], 
                ];
            });


            return [
                'extras' => $extras,
                'addons' => $addons,
                'excludes' => $excludes,
                'product' => $product,
                'variations' => $variations
            ];
        });

        return response()->json([
            'id' => $orders->id,
            'notes' => $orders->notes,
            'table' => $orders?->table?->table_number,
            'location' => $orders?->table?->location?->name,
            'cart' => $cart,
            'kitchen' => $kitchen,
        ]);
    }

    public function status(Request $request, $id){
        $this->order_carts
        ->where('id', $id)
        ->update([
            'prepration_status' => 'pick_up'
        ]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
