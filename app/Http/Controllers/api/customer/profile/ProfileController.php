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
        return response()->json([
            'data' => $request->user()
        ]);
    }

    public function update_profile(ProfileRequest $request){
        // https://backend.food2go.pro/customer/profile/update
        // Keys
        // f_name, l_name, email, phone, bio, address => key = value
        // password, image
        $customer = $request->user();
        $customer->f_name = $request->f_name ?? null;
        $customer->l_name = $request->l_name ?? null;
        $customer->email = $request->email ?? null;
        $customer->phone = $request->phone ?? null;
        $customer->bio = $request->bio ?? null;
        $customer->address = $request->address && is_string($request->address) 
        ? $request->address : json_encode($request->address);
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
}
