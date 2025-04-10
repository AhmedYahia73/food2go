<?php

namespace App\Http\Controllers\api\cashier\customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\admin\pos\OrderRequest;

use App\Models\Customer;
use App\Models\Address;

class CustomerController extends Controller
{
    public function __construct(private Customer $customers,
    private Address $address){}
    
    public function view(){
        // /cashier/customer
        $customers = $this->customers
        ->with('addresses.zone.city:id,name')
        ->get();

        return response()->json([
            'customers' => $customers,
        ]);
    }

    public function create(Request $request){
        // /cashier/customer/add
        // Keys
        // name, phone, 
        // addresses[{zone_id, address, street, building_num, floor_num, apartment, additional_data, type, map}]
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required|unique:customers',
            'addresses.*.zone_id' => 'required|exists:zones,id',
            'addresses.*.address' => 'required',
            'addresses.*.street' => 'sometimes',
            'addresses.*.building_num' => 'sometimes',
            'addresses.*.floor_num' => 'sometimes',
            'addresses.*.apartment' => 'sometimes',
            'addresses.*.additional_data' => 'sometimes',
            'addresses.*.type' => 'sometimes',
            'addresses.*.map' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }
        $validated = $validator->validated();
        
        $customer = $this->customers
        ->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);
        if (isset($validated['addresses'])) {
            foreach ($validated['addresses'] as $item) {
                $item['customer_id'] = $customer->id;
                $this->address->create($item);
            }
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // cashier/customer/update/{id}
        // name, phone
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => "required|unique:customers,phone,$id",
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }
        $customerRequest = $validator->validated();
        $this->customers
        ->where('id', $id)
        ->update($customerRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
