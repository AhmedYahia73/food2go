<?php

namespace App\Http\Controllers\api\cashier\user;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Address;

class UserController extends Controller
{
    public function __construct(private User $user,
    private Address $address){}

    public function view(Request $request){
        $users = $this->user
		->select("id", "f_name", "l_name", "image", "phone", "phone_2")
        ->where('status', 1)
        ->with(['address' => function($query){
            return $query->with(['zone:id,zone,price', 'city:id,name']);
        }])
        ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    public function user(Request $request, $id){
        $user = $this->user
        ->where('status', 1)
        ->where('id', $id)
        ->with(['address' => function($query){
            return $query->with(['zone:id,zone,price', 'city:id,name']);
        }])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "f_name" => $item->f_name,
                "l_name" => $item->l_name,
                "image_link" => $item->image_link,
                "phone" => $item->phone,
                "phone_2" => $item->phone_2,
                "address" => $item->address,
            ];
        });

        return response()->json([
            'user' => $user,
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|unique:users,phone',
            'phone_2' => 'sometimes|unique:users,phone_2',
            'addresses.*.zone_id' => 'required|exists:zones,id',
            'addresses.*.address' => 'required',
            'addresses.*.street' => 'sometimes',
            'addresses.*.building_num' => 'sometimes',
            'addresses.*.floor_num' => 'sometimes',
            'addresses.*.apartment' => 'sometimes',
            'addresses.*.additional_data' => 'sometimes',
            'addresses.*.type' => 'sometimes',
            'addresses.*.map' => 'sometimes',
            'addresses.*.city_id' => 'required|exists:cities,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $userRequest = $validator->validated();
        $userRequest['signup_pos'] = 1;
        $userRequest['password'] = $request->phone;
        $userRequest['email'] = $request->phone . '@gmail.com';
        $user = $this->user
        ->create($userRequest); 
        $addresses = $request->addresses;
        $user->address()
        ->createMany($addresses); 

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'f_name' => 'sometimes',
            'l_name' => 'sometimes',
            'phone' => 'sometimes|unique:users,phone,' . $id,
            'phone_2' => 'sometimes|unique:users,phone_2,' . $id,
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $userRequest = $validator->validated(); 
        if($request->phone && !empty($request->phone)){
            $userRequest['password'] = bcrypt($request->phone);
            $userRequest['email'] = $request->phone . '@gmail.com';
        }
        $this->user
        ->where('id', $id)
        ->update($userRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    } 
}
