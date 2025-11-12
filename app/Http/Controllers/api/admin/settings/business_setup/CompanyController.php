<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\admin\settings\bussiness_setup\CompanyRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\trait\image;

use App\Models\CompanyInfo;
use App\Models\Maintenance;
use App\Models\Currency;
use App\Models\Setting;
use Carbon\Carbon;

class CompanyController extends Controller
{
    public function __construct(private CompanyInfo $company_info, 
    private Currency $currency, private Maintenance $maintenance, 
    private Setting $settings){}
    use image;
     
  
    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/company
        $company_info = $this->company_info
        ->orderByDesc('id')
        ->first();
        $currency = $this->currency
        ->select('id', 'currancy_name as name')
        ->get();
        $maintenance = $this->maintenance
        ->orderByDesc('id')
        ->first(); 
        $website = $this->settings
        ->where("name", "web_site")
        ->first()?->setting;
        $qr_code = $this->settings
        ->where("name", "web_site_qr")
        ->first()?->setting;
        $qr_code = url('storage/' . $qr_code);

        return response()->json([
            'company_info' => $company_info,
            'currency' => $currency,
            'maintenance' => $maintenance,
            'website' => $website,
            'qr_code' => $qr_code,
        ]);
    }

    public function add(CompanyRequest $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/company/add
        // Keys
        // name, phone, email, address, logo, fav_icon, time_zone, time_format => [24hours,am/pm],
        // currency_id, currency_position => [left,right], copy_right, logo, fav_icon, country,
        // phone2, watts, android_link, ios_link, order_online, android_switch, ios_switch, cover_app_image
        // cover_app_image
        $companyRequest = $request->validated(); 
        $companyRequest['time_zone'] = is_string($companyRequest['time_zone']) ?
        json_decode($companyRequest['time_zone']):$companyRequest['time_zone'];
        $companyRequest['time_zone'] = $companyRequest['time_zone'];
        $companyRequest['time_zone'] = str_replace('"', '', $companyRequest['time_zone']);
        $company_info = $this->company_info
        ->orderByDesc('id')
        ->first();
        $maintenance = [];
        if (empty($company_info)) {
            $validator = Validator::make($request->all(), [
                'cover_app_image' => ['required'],
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            if ($request->logo) {
                $logo = $this->upload($request, 'logo', 'admin/settings/business_setup/company/logo');
                $companyRequest['logo'] = $logo;
            }
            if ($request->fav_icon) {
                $fav_icon = $this->upload($request, 'fav_icon', 'admin/settings/business_setup/company/fav_icon');
                $companyRequest['fav_icon'] = $fav_icon;
            }
            if ($request->cover_app_image) {
                $cover_app_image = $this->upload($request, 'cover_app_image', 'admin/settings/business_setup/company/cover_app_image');
                $companyRequest['cover_app_image'] = $cover_app_image;
            }
            $company_info = $this->company_info->create($companyRequest);
        }
        else {
            if (!is_string($request->logo)) {
                $logo = $this->upload($request, 'logo', 'admin/settings/business_setup/company/logo');
                $this->deleteImage($company_info->logo);
                $companyRequest['logo'] = $logo;
            }
            if (!is_string($request->fav_icon)) {
                $fav_icon = $this->upload($request, 'fav_icon', 'admin/settings/business_setup/company/fav_icon');
                $this->deleteImage($company_info->fav_icon);
                $companyRequest['fav_icon'] = $fav_icon;
            }
            if (!is_string($request->cover_app_image)) {
                $cover_app_image = $this->upload($request, 'cover_app_image', 'admin/settings/business_setup/company/cover_app_image');
                $this->deleteImage($company_info->cover_app_image);
                $companyRequest['cover_app_image'] = $cover_app_image;
            }
            $company_info->update($companyRequest);
        }
        if (isset($request->maintenance) && $request->maintenance) {
            
            $validator = Validator::make($request->all(), [
                'maintenance.all' => ['boolean'],
                'maintenance.branch' => ['boolean'],
                'maintenance.customer' => ['boolean'],
                'maintenance.web' => ['boolean'],
                'maintenance.delivery' => ['boolean'],
                'maintenance.day' => ['required', 'boolean'],
                'maintenance.week' => ['required', 'boolean'],
                'maintenance.until_change' => ['required', 'boolean'],
                'maintenance.customize' => ['required', 'boolean'],
                'maintenance.start_date' => ['date'],
                'maintenance.end_date' => ['date'],
                'maintenance.status' => ['required', 'boolean'],
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $maintenanceRequest = $request->maintenance;
            $currentDate = Carbon::now();
            $maintenance = $this->maintenance
            ->orderByDesc('id')
            ->first();
            if ($request->day) {
                $maintenanceRequest['start_date'] = date('Y-m-d');
                $maintenanceRequest['end_date'] = $currentDate->addDay();
            }
            elseif ($request->week) {
                $maintenanceRequest['start_date'] = date('Y-m-d');
                $maintenanceRequest['end_date'] = $currentDate->addDay(7); 
            }
            if (!empty($maintenance)) {
                $maintenance->update($maintenanceRequest);
            } else {
                $maintenance = $this->maintenance
                ->create($maintenanceRequest);
            }
        }

        // __________________________
         
        $web_site = $request->web_site;
        if (substr($web_site, 0, 8) !== 'https://') {
            $web_site = 'https://' . ltrim($web_site, '/'); // نتأكد مفيش // في الأول
        }
        $website = $this->settings
        ->where("name", "web_site")
        ->first();
        $qr_code = $this->settings
        ->where("name", "web_site_qr")
        ->first();
        if($website){
            $website
            ->update([
                "setting" => $web_site
            ]);
        }
        else{
            $this->settings
            ->create([
                'name' => "web_site",
                "setting" => $web_site
            ]);
        }
        $qrImage = QrCode::format('png')->size(300)->generate($web_site);
        $fileName = 'admin/website/qr/'. time() . rand(0, 10000) .'.png';
        Storage::disk('public')->put($fileName, $qrImage);

        if($qr_code){

            $this->deleteImage($qr_code->setting);
            $qr_code
            ->update([
                "setting" => $fileName
            ]);
        }
        else{
            $this->settings
            ->create([
                'name' => "web_site_qr",
                "setting" => $fileName
            ]);
        }
        return response()->json([
            'company_info' => $company_info,
            'maintenance' => $maintenance,
            'request' => $request->all()
        ]);
    }
}
