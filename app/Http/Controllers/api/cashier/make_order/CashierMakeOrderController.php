<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

class CashierMakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Category $category, private BranchOff $branch_off, 
    private CafeLocation $cafe_location){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;

    public function new_order(OrderRequest $request){
        // /admin/pos/order/make_order
        // Keys
        // date, amount, total_tax, total_discount
        // notes, payment_method_id, order_type, user_id
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:customers,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $request->merge([ 
            'customer_id' => $request->user_id,
            'branch_id' => $request->user()->branch_id 
        ]);
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
