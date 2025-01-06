<?php

namespace App\Http\Controllers\api\customer\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\profile\ProfileRequest;
use App\trait\image;

class ProfileController extends Controller
{
    public function __construct(){}
    use image;

    public function profile_data(Request $request){
        // https://bcknd.food2go.online/customer/profile/profile_data
        return response()->json([
            'data' => $request->user()
        ]);
    }

    public function update_profile(ProfileRequest $request){
        // https://bcknd.food2go.online/customer/profile/update
        // Keys
        // f_name, l_name, email, phone, bio, address => key = value
        // password, image
        $customer = $request->user();
        $customer->f_name = $request->f_name ?? $customer->f_name;
        $customer->l_name = $request->l_name ?? $customer->l_name;
        $customer->email = $request->email ?? $customer->email;
        $customer->phone = $request->phone ?? $customer->phone;
        $customer->bio = $request->bio ?? $customer->bio;
        if ($request->password && !empty($request->password)) {
            $customer->password = $request->password;
        }
        if (is_file($request->image)) {
            $this->deleteImage($customer->image);
            $imag_path = $this->upload($request, 'image', 'users/customers/image');
            $customer->image = $imag_path;
        }
        $customer->save();

        return response()->json([
            'success' => 'You update customer success'
        ]);
    }

    public function delete_account(Request $request){
        $request->user()->delete();

        return response()->json([
            'success' => 'You delete account success'
        ]);
    }
}
