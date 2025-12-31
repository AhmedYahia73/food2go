<?php

namespace App\Http\Controllers\api\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\Notifications; 

use App\Models\KitchenOrder;
use App\Models\TranslationTbl;
use App\Models\OrderCart;
use App\Models\Order;
use App\Models\Waiter;

class OrderController extends Controller
{
    public function __construct(private KitchenOrder $kitchen_order,
    private OrderCart $order_carts, private Order $order,
    private Waiter $waiters){}
    use Notifications;

    public function kitchen_orders(Request $request)
    {
        // TranslationTbl
        $kitchen_orders = $this->kitchen_order
            ->where('kitchen_id', $request->user()->id)
            ->where('status', 0)
            ->get();

        $locale = $request->locale ?? 'en';

        /*
        |--------------------------------------------------------------------------
        | Load translations once
        |--------------------------------------------------------------------------
        */
        $translations = TranslationTbl::where('locale', $locale)
            ->orderByDesc('id')
            ->get()
            ->groupBy('key')
            ->map(fn ($rows) => $rows->first()->value);

        /*
        |--------------------------------------------------------------------------
        | Build response
        |--------------------------------------------------------------------------
        */
        $result = $kitchen_orders->map(function ($item) use ($translations) {

            $translate = fn ($key) => $translations[$key] ?? $key;

            $orders = collect($item->order)->map(function ($orderItem) use ($translate) {

                return [
                    'id' => $orderItem->id,
                    'name' => $translate($orderItem->name),
                    'count' => $orderItem->count,
                    'price' => $orderItem->price,
                    'price_after_discount' => $orderItem->price_after_discount,
                    'price_after_tax' => $orderItem->price_after_tax,
                    'notes' => $orderItem->notes,

                    'addons_selected' => collect($orderItem->addons_selected ?? [])
                        ->map(fn ($addon) => [
                            'name' => $translate($addon->name),
                            'count' => $addon->count,
                        ]),

                    'excludes' => collect($orderItem->excludes ?? [])
                        ->map(fn ($exclude) => [
                            'name' => $translate($exclude->name),
                        ]),

                    'extras' => collect($orderItem->extras ?? [])
                        ->map(fn ($extra) => [
                            'name' => $translate($extra->name),
                        ]),

                    'variation_selected' => collect($orderItem->variation_selected ?? [])
                        ->map(fn ($variation) => [
                            'name' => $translate($variation->name),
                            'type' => $variation->type ?? null,
                            'options' => collect($variation->options ?? [])
                                ->map(fn ($opt) => [
                                    'name' => $translate($opt->name),
                                ]),
                        ]),
                ];
            });

            return [
                'id' => $item->id,
                'order' => $orders,
                'table' => $item->table,
                'type' => $item->type,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'kitchen_order' => $result
        ]);
    }


    public function done_status(Request $request, $id){
        $kitchen_order = $this->kitchen_order
        ->where('id', $id)
        ->first();
        $kitchen_order->update([
            'status' => 1
        ]);
        if($kitchen_order->type == 'dine_in'){
            $orders = $this->kitchen_order
            ->where('table_id', $kitchen_order->table_id)
            ->where('status', 0)
            ->first();
            if(!empty($orders)){
				return $orders;
                return response()->json([
                    'success' => 'You change status success'
                ]);
            }
            $location_ids = $orders?->table
            ?->pluck('location_id')
			?->toArray() ?? [];
            $waiter_tokens = $this->waiters
            ->whereHas('locations', function($query) use($location_ids){
                $query->whereIn('cafe_locations.id', $location_ids);
            })
            ->pluck('fcm_token')->filter()
    		->values()
			->toArray();
            $this->order_carts
            ->where('table_id', $kitchen_order->table_id)
            ->update([
                'prepration_status' => 'done'
            ]);
            try {
                $this->sendNotificationToMany($waiter_tokens, 'Order Done', 'Table Name Is : ' . $orders?->table?->table_number ?? ''); 
            } catch (\Throwable $th) {
                return response()->json([
                    'errors' => 'Notification does not reach'
                ]);
            }
        }
        elseif($kitchen_order->type == 'take_away'){
            $orders = $this->kitchen_order
            ->where('order_id', $kitchen_order->order_id)
            ->where('status', 0)
            ->first();
            if(!empty($orders)){
                return response()->json([
                    'success' => 'You change status success'
                ]);
            }
            $this->order
            ->where('id', $kitchen_order->order_id)
            ->update([
                'take_away_status' => 'done'
            ]);
        }
        else{
            $orders = $this->kitchen_order
            ->where('order_id', $kitchen_order->order_id)
            ->where('status', 0)
            ->first();
            if(!empty($orders)){
                return response()->json([
                    'success' => 'You change status success'
                ]);
            }
            $this->order
            ->where('id', $kitchen_order->order_id)
            ->update([
                'delivery_status' => 'done'
            ]);
        } 

        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    public function notification(Request $request){
        $kitchen_order = $this->kitchen_order
        ->where('kitchen_id', $request->user()->id)
        ->where('read_status', false)
        ->where('status', 0)
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'order' => $item->order,
                'table' => $item->table,
                'type' => $item->type,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'kitchen_order' => $kitchen_order
        ]);
    }

    public function read_status(Request $request, $id){
        $kitchen_order = $this->kitchen_order
        ->where('id', $id)
        ->update([
            'read_status' => true
        ]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
