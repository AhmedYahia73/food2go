<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\pos\OrderRequest;

use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderInformation;

class PosSaleController extends Controller
{
    public function __construct(private Category $categories, private Customer $customers,
    private User $users, private Discount $discounts, private Tax $taxes,
    private Branch $branches, private Order $orders, private OrderInformation $order_information){}
    protected $orderRequest = [
        'date',
        'user_id',
        'branch_id',
        'amount',
        'order_status',
        'order_type',
        'total_tax',
        'total_discount',
        'paid_by',
        'payment_status',
        'address',
    ];
    protected $personal_information = [
        'name',
        'number',
        'road',
        'house',
        'floor',
    ];

    public function sale(){
        $categories = $this->categories
        ->with(['sub_categories.products.addons'])
        ->get();
        $users = $this->users->get();
        $discounts = $this->discounts->get();
        $taxes = $this->taxes->get();
        $branches = $this->branches->get();

        return response()->json([
            'categories' => $categories,
            'users' => $users,
            'discounts' => $discounts,
            'taxes' => $taxes,
            'branches' => $branches,
        ]);
    }

    public function add_order_user(OrderRequest $request){
         //  لما يكون اللى هيشترى user
        // Keys
        // date, branch_id, user_id, amount, order_status, order_type
        // total_tax, total_discount, paid_by, payment_status, address
        // name, number, road, house, floor, personal_address
        // products_id[], addons_id[]
        $orderRequest = $request->only($this->orderRequest);
        $orderRequest['pos'] = true;
        if (!$request->date) {
            $orderRequest['date'] = now();
        }
        $order = $this->orders->create($orderRequest);
        if (!empty($request->name)) {
            $order_information = $request->only($this->personal_information);
            $order_information['order_id'] = $order->id;
            $order_information['address'] = $request->personal_address;
            $this->order_information->create($order_information);
        }
        if ($request->products_id) {
            $order->products()->attach($request->products_id);
        }
        if ($request->addons_id) {
            $order->addons()->attach($request->addons_id);
        }

        return response()->json([
            'success' => 'You Add data success'
        ]);
    }
}
