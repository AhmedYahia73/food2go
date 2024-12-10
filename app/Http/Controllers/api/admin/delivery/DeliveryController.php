<?php

namespace App\Http\Controllers\api\admin\delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\delivery\DeliveryRequest;
use App\Http\Requests\admin\delivery\UpdateDeliveryRequest;
use App\trait\image;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Delivery;
use App\Models\Branch;
use App\Models\Order;

class DeliveryController extends Controller
{
    public function __construct(private Delivery $deliveries, private Branch $branches,
    private Order $orders){}
    protected $deliveryRequest = [
        'f_name',
        'l_name',
        'identity_type',
        'identity_number',
        'email',
        'phone',
        'branch_id',
        'status',
        'phone_status',
        'chat_status',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/delivery
        $deliveries = $this->deliveries
        ->get();
        $branches = $this->branches->get();

        return response()->json([
            'deliveries' => $deliveries,
            'branches' => $branches,
        ]);
    }

    public function delivery($id){
        // https://bcknd.food2go.online/admin/delivery/item/{id}
        $delivery = $this->deliveries
        ->with('branch')
        ->where('id', $id)
        ->first(); 

        return response()->json([
            'delivery' => $delivery, 
        ]);
    }

    public function history($id){
        // https://bcknd.food2go.online/admin/delivery/history/{id}
        $orders = $this->orders
        ->where('delivery_id', $id)
        ->whereIn('order_status', ['confirmed', 'delivered', 'returned', 'faild_to_deliver', 'canceled'])
        ->with(['address.zone' => function($query){
            $query->with(['city', 'branch']);
        }])
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function filter_history(Request $request, $id){
        // https://bcknd.food2go.online/admin/delivery/history_filter/{id}
        // Keys
        // from, to
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $orders = $this->orders
        ->where('delivery_id', $id)
        ->whereIn('order_status', ['confirmed', 'delivered', 'returned', 'faild_to_deliver', 'canceled'])
        ->where('updated_at', '>=', $request->from)
        ->where('updated_at', '<=', $request->to)
        ->with(['address.zone' => function($query){
            $query->with(['city', 'branch']);
        }])
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/delivery/status/{id}
        // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->deliveries->where('id', $id)
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

    public function create(DeliveryRequest $request){
        // https://bcknd.food2go.online/admin/delivery/add
        // Keys
        // f_name, l_name, identity_type, identity_number, email, phone
        // password, branch_id, status, image, identity_image
        $deliveryRequest = $request->only($this->deliveryRequest);
        $deliveryRequest['password'] = $request->password;
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/delivery/image');
            $deliveryRequest['image'] = $imag_path;
        }
        if (is_file($request->identity_image)) {
            $imag_path = $this->upload($request, 'identity_image', 'users/delivery/identity_image');
            $deliveryRequest['identity_image'] = $imag_path;
        }
        $this->deliveries->create($deliveryRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(UpdateDeliveryRequest $request, $id){
        // https://bcknd.food2go.online/admin/delivery/update/{id}
        // Keys
        // f_name, l_name, identity_type, identity_number, email, phone
        // password, branch_id, status, image, identity_image
        $deliveryRequest = $request->only($this->deliveryRequest);
        $delivery = $this->deliveries
        ->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/delivery/image');
            $deliveryRequest['image'] = $imag_path;
            $this->deleteImage($delivery->image);
        }
        if (is_file($request->identity_image)) {
            $imag_path = $this->upload($request, 'identity_image', 'users/delivery/identity_image');
            $deliveryRequest['identity_image'] = $imag_path;
            $this->deleteImage($delivery->identity_image);
        }
        if (!empty($request->password)) { 
            $deliveryRequest['password'] = $request->password;
        }
        $delivery->update($deliveryRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/delivery/delete/{id}
        $delivery = $this->deliveries
        ->where('id', $id)
        ->first();
        $this->deleteImage($delivery->image);
        $this->deleteImage($delivery->identity_image);
        $delivery->delete();
        
        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
