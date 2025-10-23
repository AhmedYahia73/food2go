<?php

namespace App\Http\Controllers\api\admin\customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\customer\CustomerRequest;
use App\Http\Requests\admin\customer\UpdateCustomerRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;
use Carbon\Carbon;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TimeSittings;
use App\Models\FinantiolAcounting;
use App\Models\UserPaidDebt;
use App\Models\UserDue; 

class CustomerController extends Controller
{
    public function __construct(private User $customers,
    private Order $orders, private OrderDetail $order_details
    , private TimeSittings $TimeSittings, 
    private UserDue $user_due, private UserPaidDebt $user_debt){}
    protected $customerRequest = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'phone_2',
        'password',
        'due_status',
        'max_due', 
        'status',
    ];
    protected $customerUpdateRequest = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'phone_2',
        'due_status',
        'max_due', 
        'status',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/customer
        $customers = $this->customers
        ->where('deleted_at', 0)
        ->withSum('orders', 'amount')
        ->withCount('orders')
        ->get();

        return response()->json([
            'customers' => $customers,
        ]);
    }

    public function single_page(Request $request, $id){
        $orders = Order::where('user_id', $id)
        ->with('branch')
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "order_type" => $item->order_type,
                "branch" => $item?->branch?->name,
                "amount" => $item->amount,
                "date" => $item->created_at->format('Y-m-d'),
                "time" => $item->created_at->format('h:i A'),
                "order_status" => $item->pos ? null : $item->order_status,
                "order" => $item->pos ? "POS" : "Online",
                "order_number" => $item->order_number,
            ];
        });
        $total_amount = Order::where('user_id', $id)
        ->where(function($query){
            $query->where('order_status', 'delivered')
            ->orWhere("pos", 1);
        })
        ->sum("amount");
        $orders_ids = $orders?->pluck("id")?->toArray() ?? [];
        $greatest_product = $this->order_details
        ->selectRaw("product_id, SUM(count) as product_count")
        ->whereIn('order_id', $orders_ids)
        ->whereNull('exclude_id')
        ->whereNull('addon_id')
        ->whereNull('offer_id')
        ->whereNull('extra_id')
        ->whereNull('variation_id')
        ->whereNull('option_id')
        ->whereNull('deal_id')
        ->whereNotNull('product_id')
        ->whereHas("order", function($query) use($id){
            $query->where("orders.user_id", $id);
        })
        ->whereHas("product")
        ->groupBy('product_id')
        ->get()
        ->sortByDesc("product_count")
        ->first();
        $user = $this->customers
        ->where("id", $id)
        ->first();
        $user_info = [
            "id" => $id,
            "f_name" => $user->f_name,
            "l_name" => $user->l_name,
            "email" => $user->email,
            "phone" => $user->phone,
            "phone_2" => $user->phone_2,
            "points" => $user->points,
            "image_link" => $user->image_link, 
        ];

        if ($greatest_product) {
            $greatest_product->load('product');
            $greatest_product = $greatest_product->product;
            if($greatest_product){
                $greatest_product = [
                    "id" => $greatest_product->id,
                    "name" => $greatest_product->name,
                    "description" => $greatest_product->description,
                    "image" => $greatest_product->image_link,
                ];
            }
        }
        else{
           $greatest_product = null; 
        }
        $paid_debt = $this->user_debt
        ->where("user_id", $id)
        ->with(["cashier", "financial", "admin"])
        ->get()
        ->map(function($item){
            return [
                "amount" => $item->amount,
                "cashier" => $item?->cashier?->user_name,
                "admin" => $item?->admin?->name,
                "date" => $item->created_at,
                "financial" => $item?->financial?->map(function($element){
                    return [
                        "financial" => $element->name,
                        "amount" => $element?->pivot?->amount,
                    ];
                }),
            ];
        });
        $order_due = $this->user_due
        ->where("user_id", $id)
        ->get()
        ->map(function($item){
            return [
                "amount" => $item->amount,
                "order_id" => $item->order_id,
                "cashier" => $item?->cashier?->user_name,
                "order_number" => $item?->order?->order_number,
            ];
        });
         
        // user_debt user_due

        return response()->json([
            "orders" => $orders,
            "total_amount" => $total_amount,
            'greatest_product' => $greatest_product,
            'user_info' => $user_info,
            'due' => $user->due,
            'paid_debt' => $paid_debt,
            "order_due" => $order_due
        ]);
    }

    public function pay_debit(Request $request){
        $validator = Validator::make($request->all(), [
            'financials' => 'required|array',
            'financials.*.id' => 'required|exists:finantiol_acountings,id',
            'financials.*.amount' => 'required|numeric',
            'amount' => 'required|numeric',
            'user_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $user_debt = $this->user_debt
        ->create([
            'user_id' => $request->user_id , 
            'cashier_id' =>  $request->user()->role == "cashier" ? $request->user()->id : null ,
            'admin_id' =>  $request->user()->role == "admin" ? $request->user()->id : null,
            'amount' => $request->amount,
        ]);
        $user = $this->customers
        ->where("id", $request->user_id)
        ->first();
        $user->update([
            "due" => $user->due - $request->amount
        ]);

        foreach ($request->financials as $item) {
            $user_debt->financial()
            ->attach($item['id'], ["amount" => $item['amount']]);
        }

        return response()->json([
            'success' => 'You pament success'
        ]);
    }

    public function single_page_filter(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = $request->from_date . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $request->to_date . ' ' . $end->format("H:i:s");
                $end = Carbon::parse($end);
                $end = $end->addDay();
            }
			if($start >= now()){
                $end = $request->to_date . ' ' . $end->format("H:i:s");
                $end = Carbon::parse($end);
                $start = $start->subDay();
			} 
        } else {
            $start = Carbon::parse($request->from_date . ' 00:00:00');
            $end = Carbon::parse($request->to_date . ' 23:59:59');
        } 
        $start = $start->subDay(); 
        $orders = Order::where('user_id', $id)
        ->whereBetween('created_at', [$start, $end])
        ->with('branch')
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "order_type" => $item->order_type,
                "branch" => $item?->branch?->name,
                "amount" => $item->amount,
                "date" => $item->created_at->format('Y-m-d'),
                "time" => $item->created_at->format('h:i A'),
                "order_status" => $item->pos ? null : $item->order_status,
                "order" => $item->pos ? "POS" : "Online",
                "order_number" => $item->order_number,
            ];
        });
        $total_amount = Order::where('user_id', $id)
        ->whereBetween('created_at', [$start, $end])
        ->where(function($query){
            $query->where('order_status', 'delivered')
            ->orWhere("pos", 1);
        })
        ->sum("amount");
        $orders_ids = $orders?->pluck("id")?->toArray() ?? [];
        $greatest_product = $this->order_details
        ->selectRaw("product_id, SUM(count) as product_count")
        ->whereIn('order_id', $orders_ids)
        ->whereHas("order", function($query) use($id, $start, $end){
            $query->where("orders.user_id", $id)
        	->whereBetween('orders.created_at', [$start, $end]);
        })
        ->whereHas("product")
        ->whereNull('exclude_id')
        ->whereNull('addon_id')
        ->whereNull('offer_id')
        ->whereNull('extra_id')
        ->whereNull('variation_id')
        ->whereNull('option_id')
        ->whereNull('deal_id')
        ->whereNotNull('product_id')
        ->groupBy('product_id')
        ->get()
        ->sortByDesc("product_count")
        ->first();
        if ($greatest_product) {
            $greatest_product->load('product'); 
            $greatest_product = $greatest_product->product;
            if($greatest_product){
                $greatest_product = [
                    "id" => $greatest_product->id,
                    "name" => $greatest_product->name,
                    "description" => $greatest_product->description,
                    "image" => $greatest_product->image_link,
                ];
            }
        }
        else{
           $greatest_product = null; 
        }

        return response()->json([
            "orders" => $orders,
            "total_amount" => $total_amount,
            'greatest_product' => $greatest_product
        ]);
    }

    public function due_user(Request $request){
        $users = User::
        where("due", ">", 0)
        ->get()
        ->select("id", "due", "name", "image_link", "phone", "phone_2", "email");
        $financials = FinantiolAcounting::
        select("products.id", "products.name")
        ->where("status", 1)
        ->get();
        return response()->json([
            "users" => $users,
            "financials" => $financials
        ]);
    }

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/customer/status/{id}
        // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->customers->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        if ($request->status == 0) {
            return response()->json([
                'success' => 'banned'
            ]);
        } else {
            return response()->json([
                'success' => 'active'
            ]);
        }
    }

    public function create(CustomerRequest $request) {
        // https://bcknd.food2go.online/admin/customer/add
        // Keys
        // f_name, l_name, email, phone, password, status, image, phone_2
        $data = $request->only($this->customerRequest);
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'users/customers/image');
            $data['image'] = $imag_path;
        }
        $user = $this->customers->create($data);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }


    public function customer($id){
        // https://bcknd.food2go.online/admin/customer/item/{id}
        $customer = $this->customers
        ->where('id', $id)
        ->withSum('orders', 'amount')
        ->withCount('orders')
        ->first();

        return response()->json([
            'customer' => $customer,
        ]);
    }

    public function modify(UpdateCustomerRequest $request, $id){
        // https://bcknd.food2go.online/admin/customer/update/2
        // Keys
        // f_name, l_name, email, phone, password, status, image, phone_2
        $data = $request->only($this->customerUpdateRequest);
        $user = $this->customers
        ->where('id', $id)
        ->first();
        if (!is_string($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/customers/image');
            $data['image'] = $imag_path;
            $this->deleteImage($user->image);
        }
        if (!empty($request->password)) {
            $data['password'] = $request->password;
            PersonalAccessToken::
            where('name', 'customer')
            ->where('tokenable_id', $id)
            ->delete(); 
        }
        $user->update($data);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/customer/delete/{id}
        $user = $this->customers
        ->where('id', $id)
        ->update([ 
            'deleted_at' => 1
        ]);

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
    
}
