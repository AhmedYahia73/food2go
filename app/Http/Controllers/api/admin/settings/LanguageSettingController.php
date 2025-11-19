<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class LanguageSettingController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(Request $request){
        $settings_lang = $this->settings
        ->where("name", "setting_lang")
        ->first();
        if (empty($settings_lang)) {
            $settings_lang = $this->settings
            ->create([
                'name' => 'setting_lang',
                'setting' => 'en',
            ]);
        }

        return response()->json([
            "lang" => $settings_lang->setting
        ]);
    }

    public function update(Request $request){
        $validation = Validator::make($request->all(), [
            'lang' => 'required|in:en,ar',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }

        $settings_lang = $this->settings
        ->where("name", "setting_lang")
        ->first();
        if (empty($settings_lang)) {
            $this->settings
            ->create([
                'name' => 'setting_lang',
                'setting' => $request->lang,
            ]);
        }
        else{
            $settings_lang->setting = $request->lang;
            $settings_lang->save();
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }
}
