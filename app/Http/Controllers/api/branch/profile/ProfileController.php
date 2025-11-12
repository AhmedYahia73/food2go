<?php

namespace App\Http\Controllers\api\branch\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\trait\image;

use App\Models\Branch;

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
        $branch = Branch::
        where("id", $request->user()->id)
        ->first();
        $branch->name = $request->name ?? $branch->name;
        $branch->email = $request->email ?? $branch->email;
        $branch->phone = $request->phone ?? $branch->phone;
        if ($request->password) {
            $branch->password = bcrypt($request->password) ?? $branch->password;
        }
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $this->deleteImage($branch->image);
            $branch->image = $imag_path;
        } 
        $branch->save();
        
        return response()->json([
            "success" => "You update profile success"
        ]);
    }
}
