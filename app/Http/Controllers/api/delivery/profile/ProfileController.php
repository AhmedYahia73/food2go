<?php

namespace App\Http\Controllers\api\delivery\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\delivery\profile\ProfileRequest;
use App\trait\image;

class ProfileController extends Controller
{
    public function __construct(){}
    use image;

    public function profile_data(Request $request){
        // https://bcknd.food2go.online/delivery/profile/profile_data
        return response()->json([
            'data' => $request->user()
        ]);
    }

    public function update_profile(ProfileRequest $request){
        // https://bcknd.food2go.online/delivery/profile/update
        // Keys
        // f_name, l_name, email, phone
        // password, image
        $delivery = $request->user();
        $delivery->f_name = $request->f_name ?? $delivery->f_name;
        $delivery->l_name = $request->l_name ?? $delivery->l_name;
        $delivery->email = $request->email ?? $delivery->email;
        $delivery->phone = $request->phone ?? $delivery->phone;
        if ($request->password && !empty($request->password)) {
            $delivery->password = $request->password;
        }
        if ($request->image) {
            $this->deleteImage($delivery->image);
            $imag_path = $this->upload($request, 'image', 'users/delivery/image');
            $delivery->image = $imag_path;
        }
        $delivery->save();

        return response()->json([
            'success' => 'You update delivery success'
        ]);
    }
}
