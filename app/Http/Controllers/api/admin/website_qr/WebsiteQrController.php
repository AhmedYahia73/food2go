<?php

namespace App\Http\Controllers\api\admin\website_qr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

use App\Models\Setting;

class WebsiteQrController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(Request $request){
        $website = $this->settings
        ->where("name", "web_site")
        ->first()?->setting;
        $qr_code = $this->settings
        ->where("name", "web_site_qr")
        ->first()?->setting;
        $qr_code = url('storage/' . $qr_code);

        return response()->json([
            "website" => $website,
            "qr_code" => $qr_code,
        ]);
    }

    public function createUpdate(Request $request){
        $validation = Validator::make($request->all(), [
            'web_site' => 'required',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $web_site = $request->web_site;
        if (substr($web_site, 0, 8) !== 'https://') {
            $web_site = 'https://' . ltrim($web_site, '/'); // نتأكد مفيش // في الأول
        }
        $website = $this->settings
        ->where("name", "web_site")
        ->first()?->setting;
        $qr_code = $this->settings
        ->where("name", "web_site_qr")
        ->first()?->setting;
        if($website){
            $website
            ->update([
                "setting" => $web_site
            ]);
        }
        else{
            $this->website
            ->create([
                'name' => "web_site",
                "setting" => $web_site
            ]);
        }
        $qrImage = QrCode::format('png')->size(300)->generate($web_site);
        $fileName = 'admin/website/qr/'. time() . rand(0, 10000) .'.png';
        Storage::disk('public')->put($fileName, $qrImage);

        if($qr_code){
            $qr_code
            ->update([
                "setting" => $fileName
            ]);
        }
        else{
            $this->qr_code
            ->create([
                'name' => "web_site_qr",
                "setting" => $fileName
            ]);
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }
}
