<?php

namespace App\Http\Controllers\api\admin\social_media;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use Illuminate\Support\Facades\Validator;

use App\Models\SocialMedia;

class SocialMediaController extends Controller
{ 
    use image;

    public function view(Request $request){
         $social_media = SocialMedia::
         select("id", "icon", "name", "link", "status")
         ->get();

        return response()->json([
            "social_media" => $social_media,
        ]);
    }

    public function social_item(Request $request, $id){
         $social_media = SocialMedia::
         select("id", "icon", "name", "link", "status")
         ->where("id", $id)
         ->first();

        return response()->json([
            "social_media" => $social_media,
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [ 
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        SocialMedia::
        where("id", $id)
        ->update([
            "status" => $request->status,
        ]);

        return response()->json([
            "success" => "You update status success"
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [ 
            'icon' => ['required'],
            'name' => ['required'],
            'link' => ['sometimes'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $socialRequest = $validator->validated();

        $image_path = $this->upload($request, 'icon', 'admin/social/icon');
        $socialRequest['icon'] = $image_path;
        SocialMedia::create($socialRequest);

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'icon' => ['sometimes'],
            'name' => ['required'],
            'link' => ['sometimes'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

     
        $social_item = SocialMedia::
        where("id", $id)
        ->first();
        if(empty($social_item)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $socialRequest = $validator->validated();
        if($request->icon){
            $image_path = $this->update_image($request, $social_item->icon, 'icon', 'admin/social/icon');
            $socialRequest['icon'] = $image_path;
        }
        $social_item->update($socialRequest);

        return response()->json([
            "success" => "You update data success"
        ]);
    }

    public function delete(Request $request, $id){
        $social_item = SocialMedia::
        where("id", $id)
        ->first();
        if(empty($social_item)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $this->deleteImage($social_item->icon);
        $social_item->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
