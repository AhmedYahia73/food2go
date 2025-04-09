<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;

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

use App\trait\image;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;

class PosOrderController extends Controller
{
    public function __construct(private Order $orders, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, 
    private CafeLocation $cafe_location){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;

    public function pos_orders(){
        $orders = $this->orders
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

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function new_order(OrderRequest $request){
         // cashier/make_order
        // Keys
        // date, branch_id, amount, total_tax, total_discount
        // notes, payment_method_id, order_type
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]

        // $request->merge([ 
        //     'cashier_id' => $request->user()->cashier_id,
        //     'cashier_man_id' => $request->user()->id,
        // ]);
        $order = $this->make_order($request);
        if (isset($order['errors']) && !empty($order['errors'])) {
            return response()->json($order, 400);
        }
        $this->order
        ->where('id', $order['payment']->id)
        ->update([
            'pos' => 1
        ]);
        return response()->json([
            'success' => $order['payment']->id, 
        ]);
        
    }
}
