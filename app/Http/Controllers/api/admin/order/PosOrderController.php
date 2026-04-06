<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CafeLocation;
use App\Models\CafeTable;
use App\Models\Kitchen;
use App\Models\KitchenOrder;
use App\Models\OrderCart;
use App\trait\PlaceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PosOrderController extends Controller
{
    use PlaceOrder;

    public function branches(Request $request){
        $branches = Branch::
        where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $halls = CafeLocation::
        get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "branch_id" => $item->branch_id,
            ];
        });

        return response()->json([
            "branches" => $branches,
            "halls" => $halls,
        ]);
    }
    
    public function tables(Request $request, $id){
        $tables = CafeTable::
        where("status", 1)
        ->with(["sub_table:id,table_number,current_status,capacity"])
        ->whereDoesntHave("main_table")
        ->where("location_id", $id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "table_number" => $item->table_number,
                "current_status" => $item->current_status,  
                "capacity" => $item->capacity, 
                "sub_table" => $item->sub_table 
            ];
        });

        return response()->json([
            "tables" => $tables
        ]);
    }

    public function captain_orders(Request $request, $id){
        $tables_ids = CafeTable::
        where('id', $id)
        ->orWhere('main_table_id', $id)
        ->pluck('id')
        ->toArray();
        $order_cart = OrderCart::
        whereIn('table_id', $tables_ids)
        ->whereNotNull("captain_id")
        ->get();
        $carts = [];
        foreach ($order_cart as $key => $item) {
            $order_item = $this->order_format($item, $key);
            $carts[] = $order_item;
        }

        return response()->json([
            'carts' => $carts
        ]);
    }

    public function table_order_orders(Request $request, $id){
        $tables_ids = CafeTable::
        where('id', $id)
        ->orWhere('main_table_id', $id)
        ->pluck('id')
        ->toArray();
        $order_cart = OrderCart::
        whereIn('table_id', $tables_ids)
        ->whereNull("captain_id")
        ->get();
        $carts = [];
        foreach ($order_cart as $key => $item) {
            $order_item = $this->order_format($item, $key);
            $carts[] = $order_item;
        }

        return response()->json([
            'carts' => $carts
        ]);
    }

    public function preparing(Request $request){
        $validator = Validator::make($request->all(), [
            'preparing' => 'required',
            'preparing.*.cart_id' => 'required|exists:order_carts,id',
            'preparing.*.status' => 'required|in:preparing,preparation,done,pick_up',
            'preparing.*.count' => 'required|numeric',
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $kitchen_order = [];
        $kitchen_items = [];
        $table_number = CafeTable::
        where("id", $request->table_id)
        ->first()?->table_number ?? null;
        foreach ($request->preparing as $value) {
            $order_cart = OrderCart::
            where('id', $value['cart_id'])
            ->where("prepration_status", "!=", "preparing")
            ->first();
            if(empty($order_cart)){
                continue;
            }
            $preparing = $order_cart->cart;
            $order_cart->prepration_status = $value['status'];  
            $order_cart->save();
            $order_item = $this->dine_in_print($order_cart);
            $order_item = collect($order_item);

            $element = $order_item[0];
            $kitchen = Kitchen::
            where(function($q) use($element){
                $q->whereHas('products', function($query) use ($element){
                    $query->where('products.id', $element['id']);
                })
                ->orWhereHas('category', function($query) use ($element){
                    $query->where('categories.id', $element['category_id'])
                    ->orWhere('categories.id', $element['sub_category_id']);
                });
            })
            ->where('branch_id', $request->user()->branch_id)
            ->with(["printer" => function($query){
                $query->orWhereJsonContains("module", "dine_in");
            }])
            ->first(); 
            $printers = $kitchen?->printer ?? collect([]);
            if($printers->count() > 0){ 
                $kitchen->print_name = $printers[0]->print_name;
                $kitchen->print_ip = $printers[0]->print_ip;
                $kitchen->print_status = $printers[0]->print_status;
                $kitchen->print_type = $printers[0]->print_type;
                $kitchen->print_port = $printers[0]->print_port;
            }
            if(!empty($kitchen)){
                $kitchens = Kitchen::
                where(function($q) use($element){
                    $q->whereHas('products', function($query) use ($element){
                        $query->where('products.id', $element['id']);
                    })
                    ->orWhereHas('category', function($query) use ($element){
                        $query->where('categories.id', $element['category_id'])
                        ->orWhere('categories.id', $element['sub_category_id']);
                    });
                })
                ->where('branch_id', $request->user()->branch_id)
                ->with(["printer" => function($query){
                    $query->orWhereJsonContains("module", "dine_in");
                }])
                ->get()
                ->map(function($item){ 
                    $printers = $item->printer;
                    if($printers->count() > 0){ 
                        $item->print_name = $printers[0]->print_name;
                        $item->print_ip = $printers[0]->print_ip;
                        $item->print_status = $printers[0]->print_status;
                        $item->print_type = $printers[0]->print_type;
                        $item->print_port = $printers[0]->print_port;
                    }

                    return $item;
                }); 
                $element['cart_id'] = $value['cart_id']; 
                $element['count'] = $value['count']; 
                foreach ($kitchens as $kitchen) {
                    $kitchen_items[$kitchen->id] = $kitchen;
                    $kitchen_order[$kitchen->id][] = $element;
                }
            }
        }
        $orders = []; 
        foreach ($kitchen_order as $key => $item) {
            foreach ($item as $order_element) {
                KitchenOrder::
                create([
                    'table_id' => $request->table_id,
                    'kitchen_id' => $key,
                    'order' => json_encode([$order_element]),
                    'type' => 'dine_in',
                    'cart_id' => $order_element['cart_id'],
                ]);
            }

            $orders[] = $item[0];

            // نضيف order للـ model بطريقة صحيحة
            $kitchen_items[$key]->order = $item;
        }

        $kitchen_items = array_values($kitchen_items); 

        foreach ($kitchen_items as $k => $kitchen) {

            $value_items = $kitchen->order;

            foreach ($value_items as $val_key => $value_item) {

                $items = collect($value_item);

                $peice_items = $items->where("weight", 0)->count() > 0
                ? $items->where("weight", 0)['count']
                : 0;

                $weight_items = $items->where("weight", 1)->count() > 0 ? 1 : 0;

                $value_items[$val_key]['order_count'] = $peice_items + $weight_items;
            }

            $kitchen->order = $value_items;
        }

        
        return response()->json([
            'success' => 'You perpare success',
            "kitchen_items" => $kitchen_items,
            "table_number" => $table_number
        ]);
    }
}
