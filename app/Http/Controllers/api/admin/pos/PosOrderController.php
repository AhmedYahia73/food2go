<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSale;
use App\Models\Product;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\Addon;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\PaymentMethodAuto;
use App\Models\Category;
use App\Models\Setting;
use App\Models\BranchOff;
use App\Models\CafeLocation;
use App\Models\CafeTable;

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;

class PosOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, private CafeTable $tables,
    private CafeLocation $cafe_location){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;


    // public function lists(Request $request){
    //     // /admin/pos/order/lists
    //     $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
    //     $branch_id = 0;
    //     $payment_methods = $this->paymentMethod
    //     ->where('status', 1)
    //     ->get();
    //     if ($request->branch_id && !empty($request->branch_id)) {
    //         $branch_id = $request->branch_id;
    //     }
    //     if ($request->address_id && !empty($request->address_id)) {
    //         $address = $this->address
    //         ->where('id', $request->address_id)
    //         ->first();
    //         $branch_id = $address?->zone?->branch_id;
    //     }
    //     $branch_off = $this->branch_off
    //     ->where('branch_id', $branch_id)
    //     ->get();
    //     $product_off = $branch_off->pluck('product_id')->filter();
    //     $category_off = $branch_off->pluck('category_id')->filter();
    //     $option_off = $branch_off->pluck('option_id')->filter();

    //     $categories = $this->category
    //     ->with(['sub_categories' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 
    //     'addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }])
    //     ->withLocale($locale)
    //     ->where('category_id', null)
    //     ->get()
    //     ->filter(function($item) use($category_off){
    //         return !$category_off->contains($item->id);
    //     });
    //     $products = $this->products
    //     ->with(['addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     },'sub_category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'category_addons' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'excludes' => function($query) use($locale){
    //         $query->withLocale($locale);
    //     }, 'extra' => function($query) use($locale){
    //         $query->whereNull('option_id')
    //         ->withLocale($locale);
    //     }, 'discount', 
    //     'variations' => function($query) use($locale){
    //         $query->withLocale($locale)
    //         ->with(['options' => function($query_option) use($locale){
    //             $query_option->with(['extra' => function($query_extra) use($locale){
    //                 $query_extra->with('parent_extra')
    //                 ->withLocale($locale);
    //             }])
    //             ->withLocale($locale);
    //         }]);
    //     }, 'sales_count', 'tax'])
    //     ->withLocale($locale)
    //     ->where('item_type', '!=', 'offline')
    //     ->where('status', 1)
    //     ->get()
    //     ->map(function($product) use($category_off, $product_off, $option_off){
    //         //get count of sales of product to detemine stock
    //         if ($product->stock_type == 'fixed') {
    //             $product->count = $product->sales_count->sum('count');
    //             $product->in_stock = $product->number > $product->count ? true : false;
    //         }
    //         elseif ($product->stock_type == 'daily') {
    //             $product->count = $product->sales_count
    //             ->where('date', date('Y-m-d'))
    //             ->sum('count');
    //             $product->in_stock = $product->number > $product->count ? true : false;
    //         }
    //         // return !$category_off->contains($item->id);
    //         // $category_off, $product_off, $option_off
    //         if ($category_off->contains($product->category_id) || 
    //         $category_off->contains($product->sub_category_id)
    //         || $product_off->contains($product->id)) {
    //             return null;
    //         }
    //         $product->variations = $product->variations->map(function ($variation) 
    //         use ($option_off, $product) {
    //             $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
              
    //             return $variation;
    //         });
    //         $product->addons = $product->addons->map(function ($addon) 
    //         use ($product) {
    //             $addon->discount = $product->discount;
              
    //             return $addon;
    //         });
    //         return $product;
    //     })->filter();
    //     $categories = CategoryResource::collection($categories);
    //     $products = ProductResource::collection($products);

    //     return response()->json([
    //         'categories' => $categories,
    //         'products' => $products,
    //         'payment_methods' => $payment_methods,
    //     ]);
    // }

    public function pos_orders(){
        // /admin/pos/order/orders
        $orders = $this->order
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 1)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $tables = $this->tables
        ->with('location')
        ->get();

        return response()->json([
            'orders' => $orders,
            'tables' =>$tables,
        ]);
    }

    // public function new_order(OrderRequest $request){
    //     // /admin/pos/order/make_order
    //     // Keys
    //     // date, branch_id, amount, total_tax, total_discount
    //     // notes, payment_method_id, order_type, user_id
    //     // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
    //     // variation[{variation_id, option_id[]}], count}]

    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:customers,id',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'error' => $validator->errors(),
    //         ],400);
    //     }
    //     $request->merge([ 
    //         'user_id' => $request->user_id,
    //     ]);
    //     $order = $this->make_order($request);
    //     if (isset($order['errors']) && !empty($order['errors'])) {
    //         return response()->json($order, 400);
    //     }
    //     $this->order
    //     ->where('id', $order['payment']->id)
    //     ->update([
    //         'pos' => 1
    //     ]);
    //     return response()->json([
    //         'success' => $order['payment']->id, 
    //     ]);
    // }

    public function tables_status(Request $request, $id){
        // /admin/pos/order/tables_status/{id}
        // Keys
        // occupied
        $validator = Validator::make($request->all(), [
            'occupied' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $this->tables
        ->where('id', $id)
        ->update([
            'occupied' => $request->occupied
        ]);

        return response()->json([
            'success' => $request->occupied ? 'active' : 'banned'
        ]);
    }
}
