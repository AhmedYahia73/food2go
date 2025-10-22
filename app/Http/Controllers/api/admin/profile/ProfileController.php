<?php

namespace App\Http\Controllers\api\admin\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;

use App\Models\Admin;

class ProfileController extends Controller
{
    use image;
    public function profile(Request $request){
        return response()->json([
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'phone' => $request->user()->phone,
            'image' => $request->user()->image_link,
        ]);
    }

    public function update(Request $request){
        $admin = Admin::
        where("id", $request->user()->id)
        ->first();
        $admin->name = $request->name ?? $admin->name;
        $admin->email = $request->email ?? $admin->email;
        $admin->phone = $request->phone ?? $admin->phone;
        if ($request->password) {
            $admin->password = bcrypt($request->password) ?? $admin->password;
        }
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'users/admin/image');
            $this->deleteImage($admin->image);
            $admin->image = $imag_path;
        } 
        $admin->save();
        
        return response()->json([
            "success" => "You update profile success"
        ]);
    }
}
