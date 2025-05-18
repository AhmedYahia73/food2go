<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\MenueImage;

class MenueController extends Controller
{
    public function __construct(private MenueImage $image){}
    use image;

    public function view(){
        // /admin/settings/menue
        $images = $this->image
        ->get();

        return response()->json([
            'images' => $images,
        ]);
    }

    public function status(Request $request, $id){
        // /admin/settings/menue/status/{id}
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $image = $this->image
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status ? 'active': 'banned'
        ]);
    }

    public function create(Request $request){
        // /admin/settings/menue/add
        // Keys
        // images[{image, status}]
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*.image' => 'required',
            'images.*.status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $images = $request->images;
        foreach ($images as $item) { 
            $imag_path = $this->uploadFile($item['image'], 'admin/menue');
            $this->image
            ->create([
                'image' => $imag_path,
                'status' => $item['status'],
            ]);
        }

        return response()->json([
            'success' => 'You add data success',
        ]);
    }

    public function delete($id){
        // /admin/settings/menue/delete/{id}
        $image = $this->image
        ->where('id', $id)
        ->first();
        if (empty($image)) {
            return response()->json([
                'errors' => 'Image is not found'
            ], 400);
        }
        $this->deleteImage($image->image);
        $image->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
