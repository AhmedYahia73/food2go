<?php

namespace App\Http\Controllers\api\admin\popup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Popup;

class PopupController extends Controller
{
    public function __construct(private Popup $popup){}
    use image;

    public function view(Request $request){
        $popup = $this->popup
        ->first();

        return response()->json([
            'image_en' => $popup->image_en_link ?? null,
            'image_ar' => $popup->image_ar_link ?? null,
            'name_en' => $popup->name_en ?? null,
            'name_ar' => $popup->name_ar ?? null,
            'link' => $popup->link ?? null,
            'status' => $popup->status ?? null,
        ]);
    }

    public function status(Request $request){
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $popup = $this->popup
        ->first();
        $popup->status = $request->status;
        $popup->save();

        return reponse()->json([
            "success" => "you update status success"
        ]);
    }

    public function create_or_update(Request $request){
        $validator = Validator::make($request->all(), [
            'image_en' => ['required'],
            'image_ar' => ['required'],
            'name_en' => ['required'],
            'name_ar' => ['required'],
            'link' => ['required'],
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $popupRequest = $validator->validated();
     
        $popup = $this->popup
        ->first();
        if(empty($popup)){
            $imag_path = $this->upload($request, 'image_en', 'admin/popup/image_en');
            $popupRequest['image_en'] = $imag_path;
            $imag_path = $this->upload($request, 'image_ar', 'admin/popup/image_ar');
            $popupRequest['image_ar'] = $imag_path;
            $this->popup
            ->create($popupRequest);
        }
        else{
            $imag_path = $this->upload($request, 'image_en', 'admin/popup/image_en');
            $popupRequest['image_en'] = $imag_path;
            $imag_path = $this->upload($request, 'image_ar', 'admin/popup/image_ar');
            $popupRequest['image_ar'] = $imag_path;
            $this->deleteImage($popup->image_en);
            $this->deleteImage($popup->image_ar);
            $popup->update($popupRequest);
        }

        return response()->json([
            "success" => "You update popup success"
        ]);
    }

    public function delete(Request $request){ 
        $popup = $this->popup
        ->first();
        if(empty($popup)){
            return response()->json([
                "errors" => "popup is empty"
            ], 400);
        }
        $this->deleteImage($popup->image_en);
        $this->deleteImage($popup->image_ar);
        $popup->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
