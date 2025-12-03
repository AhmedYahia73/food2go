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
        $kitchen_lang = $this->settings
        ->where("name", "kitchen_lang")
        ->first();
        if (empty($kitchen_lang)) {
            $kitchen_lang = $this->settings
            ->create([
                'name' => 'kitchen_lang',
                'setting' => 'ar',
            ]);
        }
        $brista_lang = $this->settings
        ->where("name", "brista_lang")
        ->first();
        if (empty($brista_lang)) {
            $brista_lang = $this->settings
            ->create([
                'name' => 'brista_lang',
                'setting' => 'ar',
            ]);
        }
        $cashier_lang = $this->settings
        ->where("name", "cashier_lang")
        ->first();
        if (empty($cashier_lang)) {
            $cashier_lang = $this->settings
            ->create([
                'name' => 'cashier_lang',
                'setting' => 'ar',
            ]);
        }
        $preparation_lang = $this->settings
        ->where("name", "preparation_lang")
        ->first();
        if (empty($preparation_lang)) {
            $preparation_lang = $this->settings
            ->create([
                'name' => 'preparation_lang',
                'setting' => 'ar',
            ]);
        }

        return response()->json([
            "brista_lang" => $brista_lang->setting,
            "kitchen_lang" => $kitchen_lang->setting,
            "cashier_lang" => $cashier_lang->setting,
            "preparation_lang" => $preparation_lang->setting,
        ]);
    }

    public function update(Request $request){
        $validation = Validator::make($request->all(), [
            'brista_lang' => 'required|in:en,ar',
            'kitchen_lang' => 'required|in:en,ar',
            'cashier_lang' => 'required|in:en,ar',
            'preparation_lang' => 'required|in:en,ar',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }

        $brista_lang = $this->settings
        ->where("name", "brista_lang")
        ->first();
        if (empty($brista_lang)) {
            $this->settings
            ->create([
                'name' => 'brista_lang',
                'setting' => $request->lang,
            ]);
        }
        else{
            $brista_lang->setting = $request->lang;
            $brista_lang->save();
        }
        // ____________________________
        $kitchen_lang = $this->settings
        ->where("name", "kitchen_lang")
        ->first();
        if (empty($kitchen_lang)) {
            $this->settings
            ->create([
                'name' => 'kitchen_lang',
                'setting' => $request->lang,
            ]);
        }
        else{
            $kitchen_lang->setting = $request->lang;
            $kitchen_lang->save();
        }
        // ____________________________
        $cashier_lang = $this->settings
        ->where("name", "cashier_lang")
        ->first();
        if (empty($cashier_lang)) {
            $this->settings
            ->create([
                'name' => 'cashier_lang',
                'setting' => $request->lang,
            ]);
        }
        else{
            $cashier_lang->setting = $request->lang;
            $cashier_lang->save();
        }
        // ____________________________
        $preparation_lang = $this->settings
        ->where("name", "preparation_lang")
        ->first();
        if (empty($preparation_lang)) {
            $this->settings
            ->create([
                'name' => 'preparation_lang',
                'setting' => $request->lang,
            ]);
        }
        else{
            $preparation_lang->setting = $request->lang;
            $preparation_lang->save();
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }
}
