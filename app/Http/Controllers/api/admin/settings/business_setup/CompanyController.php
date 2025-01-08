<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\admin\settings\bussiness_setup\CompanyRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\trait\image;

use App\Models\CompanyInfo;
use App\Models\Maintenance;
use App\Models\Currency;
use Carbon\Carbon;

class CompanyController extends Controller
{
    public function __construct(private CompanyInfo $company_info, 
    private Currency $currency, private Maintenance $maintenance){}
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

        return response()->json([
            'company_info' => $company_info,
            'currency' => $currency,
            'maintenance' => $maintenance,
        ]);
    }

    public function add(CompanyRequest $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/company/add
        // Keys
        // name, phone, email, address, logo, fav_icon, time_zone, time_format => [24hours,am/pm],
        // currency_id, currency_position => [left,right], copy_right, logo, fav_icon, country
        $companyRequest = $request->validated(); 
        $companyRequest['time_zone'] = is_string($companyRequest['time_zone']) ?
        json_decode($companyRequest['time_zone']):$companyRequest['time_zone'];
        $companyRequest['time_zone'] = $companyRequest['time_zone'];
        $company_info = $this->company_info
        ->orderByDesc('id')
        ->first();
        $maintenance = [];
        if (empty($company_info)) {
            if ($request->logo) {
                $logo = $this->upload($request, 'logo', 'admin/settings/business_setup/company/logo');
                $companyRequest['logo'] = $logo;
            }
            if ($request->fav_icon) {
                $fav_icon = $this->upload($request, 'fav_icon', 'admin/settings/business_setup/company/fav_icon');
                $companyRequest['fav_icon'] = $fav_icon;
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
                    'error' => $validator->errors(),
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

        return response()->json([
            'company_info' => $company_info,
            'maintenance' => $maintenance,
            'request' => $request->all()
        ]);
    }
}
