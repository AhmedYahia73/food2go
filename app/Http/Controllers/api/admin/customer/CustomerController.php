<?php

namespace App\Http\Controllers\api\admin\customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\customer\CustomerRequest;
use App\Http\Requests\admin\customer\UpdateCustomerRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\User;

class CustomerController extends Controller
{
    public function __construct(private User $customers){}
    protected $customerRequest = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'password',
        'status',
    ];
    protected $customerUpdateRequest = [
        'f_name',
        'l_name',
        'email',
        'phone', 
        'status',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/customer
        $customers = $this->customers
        ->withSum('orders', 'amount')
        ->withCount('orders')
        ->get();

        return response()->json([
            'customers' => $customers,
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
                'error' => $validator->errors(),
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
        // f_name, l_name, email, phone, password, status, image
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
        // f_name, l_name, email, phone, password, status, image
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
        ->first(); 
        $this->deleteImage($user->image);
        $user->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
