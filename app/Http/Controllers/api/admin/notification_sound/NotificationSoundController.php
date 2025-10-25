<?php

namespace App\Http\Controllers\api\admin\notification_sound;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class NotificationSoundController extends Controller
{
    public function __construct(private Setting $settings){}
    use image;

    public function view_captain(Request $request){
        $sound = $this->settings
        ->where("name", "captain_notification_sound")
        ->first()
        ?->setting;
        $sound = url("storage/" . $sound);

        return response()->json([
            "sound" => $sound
        ]);
    }

    public function update_captain(Request $request){
        $validator = Validator::make($request->all(), [
            'sound' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $sound = $this->settings
        ->where("name", "captain_notification_sound")
        ->first();

        $sound_path = $this->upload($request, 'sound', 'captain/notification');
        if($sound){
            $this->deleteImage($sound->setting);
            $sound->setting = $sound_path;
            $sound->save();
        }
        else{
            $this->settings
            ->create([
                "name" => "captain_notification_sound",
                "setting" => $sound_path,
            ]);
        }
    }

    public function view_cashier(Request $request){
        $sound = $this->settings
        ->where("name", "cashier_notification_sound")
        ->first()
        ?->setting;
        $sound = url("storage/" . $sound);

        return response()->json([
            "sound" => $sound
        ]);
    }

    public function update_cashier(Request $request){
        $validator = Validator::make($request->all(), [
            'sound' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $sound = $this->settings
        ->where("name", "cashier_notification_sound")
        ->first();

        $sound_path = $this->upload($request, 'sound', 'cashier/notification');
        if($sound){
            $this->deleteImage($sound->setting);
            $sound->setting = $sound_path;
            $sound->save();
        }
        else{
            $this->settings
            ->create([
                "name" => "cashier_notification_sound",
                "setting" => $sound_path,
            ]);
        }
    } 
}
