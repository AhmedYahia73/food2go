<?php

namespace App\Http\Controllers\api\waiter\orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\KitchenOrder;
use App\Models\OrderCart;

class OrdersController extends Controller
{
    public function __construct(private OrderCart $order_carts,
    private KitchenOrder $kitchen_order){}

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
        $cart = collect($orders->cart)
        ->map(function($item){
            $item = collect($item);
            $extras = collect($item->extras);
            $extras = $extras->select('id', 'name');
            
            $addons = collect($item->addons);
            $addons = $addons->map(function($element){
                $element = collect($element);
                $addon = collect($element->addon);
                $addon = $addon->select('id', 'name');
                return [
                    'addon' => $addon,
                    'count' => $count,
                ];
            });
             
            $excludes = collect($item->excludes);
            $excludes = $excludes->select('id', 'name');
             
            $product = collect($item->product);
            $product = $product->map(function($element){
                $element = collect($element);
                $product = collect($element->product);
                $product = $product->select('id' ,'name');
                return [
                    'product' => $product,
                    'count' => $element->count,
                ];
            });
             
            $variations = collect($item->variations);
            $variations = $variations->map(function($element){
                $element = collect($element);
                $variations = collect($element->variation);
                $variations = $variations->select('id' ,'name', 'options');
                return [
                    'variations' => $variations,
                    'count' => $element->count,
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
            'cart' => $cart
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
