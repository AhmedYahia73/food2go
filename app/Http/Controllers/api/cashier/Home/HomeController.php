<?php

namespace App\Http\Controllers\api\cashier\Home;

use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\CashierShift;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use App\Models\TimeSittings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function __construct(private Cashier $cashier,
    private Order $order, private Setting $settings){}

    public function categories(){
        $categories = Category::where('status', 1)
        ->with("translations")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->translations->where("locale", app()->getLocale())
                ->where("key", $item->name)
                ->first()?->value ?? $item->name, 
            ];
        });

        return response()->json([
            "categories" => $categories
        ]);
    }

    public function view(Request $request){
        // https://bcknd.food2go.online/cashier/home
        $locale = $request->locale ?? 'en';
        $cashiers = $this->cashier
        ->where('branch_id', $request->user()->branch_id)
        ->where('cashier_active', 0)
        ->where('status', 1)
        ->with("translations")
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "name" => $item->translations->where("locale", $locale)
                ->where("key", $item->name)
                ->first()?->value ?? $item->name,
                "branch_id" => $item->branch_id,
                "cashier_active" => $item->cashier_active,
                "created_at" => $item->created_at,
                "status" => $item->status,
                "updated_at" => $item->updated_at,
            ];
        });
 
  
        $hidden_cashiers = $this->cashier
        ->where('branch_id', $request->user()->branch_id)
        ->where('cashier_active', 1)
        ->where('status', 1)
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "name" => $item->translations->where("locale", $locale)
                ->where("key", $item->name)
                ->first()?->value ?? $item->name,
                "branch_id" => $item->branch_id,
                "cashier_active" => $item->cashier_active,
                "created_at" => $item->created_at,
                "status" => $item->status,
                "updated_at" => $item->updated_at,
            ];
        });

        return response()->json([
            'cashiers' => $cashiers,
            'hidden_cashiers' => $hidden_cashiers,
        ]);
    }

    // public function notification_sound(Request $request){
    //     $notification_sound = $this->settings
    //     ->
    // }

    public function active_cashier(Request $request, $id){
        // https://bcknd.food2go.online/cashier/home/active_cashier/{id}
         
        $cashier_log = CashierShift:: 
        where("cashier_id", $id)
        ->whereNull("end_time")
        ->first();
        $cashier_machine = Cashier::
        where("id", $id ?? 0)  
        ->first();
        if($cashier_log && !$cashier_machine->multiple){
            return response()->json([
                "errors" => "You must end shift from this pos first"
            ], 400);
        } 
        $this->cashier
        ->where('id', $id)
        ->update([
            'cashier_active' => 1
        ]);

        return response()->json([
            'success' => 'You activate cashier success'
        ]);
    }

    public function cashier_data(Request $request){
        
        $delivery_time = $this->settings
        ->where("name", "delivery_time")
        ->first()
        ->setting ?? "00:00:00";
        $orders = $this->order 
        ->where('order_active', 1)
        ->with("branch:id,name,food_preparion_time")
        ->where('cashier_man_id', $request->user()->id)
        ->orderByDesc("created_at")
        ->get()
        ->map(function($item) use($delivery_time){
            $order_status = null;
            if($item->order_type == "take_away"){
                $food_preparion_time = Carbon::parse($item?->branch?->food_preparion_time ?? "00:00");
                $food_preparion_time = $food_preparion_time->format("H:i:s");
                $order_status = $item->take_away_status;
            }
            elseif($item->order_type == "delivery"){
                $time1 = Carbon::parse($item?->branch?->food_preparion_time ?? "00:00");
                $time2 = Carbon::parse($delivery_time);
                $totalSeconds = $time1->secondsSinceMidnight() + $time2->secondsSinceMidnight();
                $result = gmdate('H:i:s', $totalSeconds);

                $food_preparion_time = $result;
                $order_status = $item->delivery_status;
            }
            elseif ($item->order_type == "dine_in") {
                $food_preparion_time = Carbon::parse($item?->branch?->food_preparion_time ?? "00:00");
                $food_preparion_time = $food_preparion_time->format("H:i:s");
            }
            return [
                "id" => $item->id,
                "order_number" => $item->order_number,
                'food_preparion_time' => $food_preparion_time,
                "created_at" => $item->created_at,
                "order_details" => $item->order_details,  
                "order_type" => $item->order_type, 
                "order_status" => $order_status, 
                "type" => $item->pos ? "Point of Sale" : "Online", 
            ];
        });
        $take_away = $orders->where('order_type', 'take_away')
        ->where('take_away_status', '!=', 'pick_up')
        ->where('order_status', '!=', 'delivered')
        ->where('order_status', '!=', 'returned')
        ->where('order_status', '!=', 'faild_to_deliver')
        ->where('order_status', '!=', 'canceled')
        ->values();
        $dine_in = $orders->where('order_type', 'dine_in')
        ->values();
        $delivery = $orders->where('order_type', 'delivery')
        ->where('order_status', '!=', 'delivered')
        ->where('order_status', '!=', 'returned')
        ->where('order_status', '!=', 'faild_to_deliver')
        ->where('order_status', '!=', 'canceled')
        ->values();
        // if($orders->order_type == 'take_away'){
        //     $orders->take_away_status = 'preparing';
        // }
        // $order->save();

        return response()->json([
            'take_away' => $take_away,
            'dine_in' => $dine_in,
            'delivery' => $delivery,
            'take_away_count' => $take_away->count(),
            'dine_in_count' => $dine_in->count(),
            'delivery_count' => $delivery->count(),
        ]);
    }

    public function product_report(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $validator = Validator::make($request->all(), [
            'category_id' => ['nullable', 'exists:categories,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:desc,asc'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $cashierManId = auth()->user()?->id ?? $request->user()?->id;
        if (!$cashierManId) {
            return response()->json([
                'errors' => 'Unauthenticated',
            ], 401);
        }

        $locale = $request->locale ?? app()->getLocale() ?? 'en';

        $orders = Order::where("is_void", 0)
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhereNull('status');
            })
            ->where('order_active', 1)
            ->where('cashier_man_id', $cashierManId);

        if ($request->from || $request->to) {
            $time_sittings = TimeSittings::get();
            $items = [];
            $count = 0;
            $to = isset($time_sittings[0]) ? $time_sittings[0] : 0;
            $from = isset($time_sittings[0]) ? $time_sittings[0] : 0;
            foreach ($time_sittings as $item) {
                $items[$item->branch_id][] = $item;
            }
            foreach ($items as $item) {
                if (count($item) > $count || (count($item) == $count && $item[count($item) - 1]->from > $to->from)) {
                    $count = count($item);
                    $to = $item[$count - 1];
                }
                if ($from->from > $item[0]->from) {
                    $from = $item[0];
                }
            }
            if ($time_sittings->count() > 0) {
                $from = $from->from;
                $end = ($request->to ?? date("Y-m-d")) . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = ($request->from ?? "1999-05-05") . ' ' . $from;
                $start = Carbon::parse($from);
                $end = Carbon::parse($end);
                $end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
                if ($start >= $end) {
                    $end = $end->addDay();
                }
                if ($start >= now()) {
                    $start = $start->subDay();
                }
            } else {
                $start = Carbon::parse(($request->from ?? date('Y-m-d')) . ' 00:00:00');
                $end = Carbon::parse(($request->to ?? date('Y-m-d')) . ' 23:59:59');
            }
            $orders = $orders->whereBetween("created_at", [$start, $end]);
        }

        $orders = $orders->get(["id", "order_details"]);

        $products = [];
        foreach ($orders as $orderItem) {
            $details = $orderItem->order_details_data;
            if (!empty($details) && is_array($details)) {
                foreach ($details as $element) {
                    if (!is_array($element)) {
                        continue;
                    }

                    $prod = null;
                    $itemCount = 1;

                    if (isset($element['product'][0]['product']) && is_array($element['product'][0]['product'])) {
                        $prod = $element['product'][0]['product'];
                        $itemCount = (int)($element['product'][0]['count'] ?? 1);
                    } elseif (isset($element['product']['product']) && is_array($element['product']['product'])) {
                        $prod = $element['product']['product'];
                        $itemCount = (int)($element['product']['count'] ?? 1);
                    } elseif (isset($element['product']['id'])) {
                        $prod = $element['product'];
                        $itemCount = (int)($element['count'] ?? 1);
                    } elseif (isset($element['product_id'])) {
                        $prod = [
                            'id' => $element['product_id'],
                            'price' => $element['price'] ?? 0,
                            'name' => $element['name'] ?? null,
                            'category_id' => $element['category_id'] ?? null,
                            'sub_category_id' => $element['sub_category_id'] ?? null,
                        ];
                        $itemCount = (int)($element['count'] ?? 1);
                    }

                    if (!$prod || !isset($prod['id'])) {
                        continue;
                    }

                    $productId = $prod['id'];
                    $itemCount = $itemCount > 0 ? $itemCount : 1;
                    $basePrice = (float)($prod['price'] ?? 0);

                    if (isset($products[$productId])) {
                        $products[$productId]['count'] += $itemCount;
                        if ($products[$productId]['price'] == 0 && $basePrice > 0) {
                            $products[$productId]['price'] = $basePrice;
                        }
                    } else {
                        $products[$productId] = [
                            'id' => $productId,
                            'count' => $itemCount,
                            'price' => $basePrice,
                            'name' => $prod['name'] ?? null,
                            'image_link' => $prod['image_link'] ?? $prod['image'] ?? null,
                            'category_id' => $prod['category_id'] ?? null,
                            'sub_category_id' => $prod['sub_category_id'] ?? null,
                        ];
                    }
                }
            }
        }

        // Eager load Product models with category relationship and translations
        $productIds = array_keys($products);
        $productModels = Product::with(['category.translations', 'translations'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $formatted = [];
        foreach ($products as $productId => $p) {
            $productModel = $productModels->get($productId);

            // Category ID & Sub Category ID (used for category filtering)
            $catId = $productModel?->category_id ?? $p['category_id'];
            $subCatId = $productModel?->sub_category_id ?? $p['sub_category_id'];

            // Filter by category_id if provided
            if ($request->filled('category_id')) {
                $targetCatId = $request->category_id;
                if ($catId != $targetCatId && $subCatId != $targetCatId) {
                    continue;
                }
            }

            // Product name (localized)
            $name = $productModel?->name ?? ($p['name'] ?? '');
            if ($locale != 'en' && $productModel) {
                $name = $productModel->translations
                    ->where('locale', $locale)
                    ->where('key', $productModel->name)
                    ->first()?->value ?? $name;
            }

            // Base price (not final_price, as requested)
            $basePrice = (float)$p['price'];
            if ($basePrice <= 0 && $productModel) {
                $basePrice = (float)$productModel->price;
            }
            $basePrice = round($basePrice, 2);

            $count = (int)$p['count'];
            $total = round($basePrice * $count, 2);

            // Image link
            $imageLink = $productModel?->image_link ?? $p['image_link'];

            // Category name via relation
            $categoryName = $productModel?->category?->name;
            if ($categoryName && $locale != 'en' && $productModel?->category) {
                $categoryName = $productModel->category->translations
                    ->where('locale', $locale)
                    ->where('key', $categoryName)
                    ->first()?->value ?? $categoryName;
            }

            $formatted[] = [
                'id' => (int)$productId,
                'name' => $name,
                'image_link' => $imageLink,
                'category_name' => $categoryName,
                'category' => $categoryName,
                'count' => $count,
                'price' => $basePrice,
                'total' => $total,
            ];
        }

        // Sort by total (asc or desc)
        $collection = collect($formatted);
        if ($request->sort === 'asc') {
            $collection = $collection->sortBy('total')->values();
        } else {
            $collection = $collection->sortByDesc('total')->values();
        }

        // Pagination
        $perPage = (int) $request->input('per_page', $request->input('limit', 10));
        $perPage = $perPage > 0 ? $perPage : 10;
        $page = (int) $request->input('page', 1);
        $page = $page > 0 ? $page : 1;

        $total = $collection->count();
        $pagedItems = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        $lastPage = (int) ceil($total / $perPage) ?: 1;

        $paginator = new LengthAwarePaginator(
            $pagedItems,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $response = $paginator->toArray();
        $response['pagination'] = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];

        return response()->json($response);
    }
}
