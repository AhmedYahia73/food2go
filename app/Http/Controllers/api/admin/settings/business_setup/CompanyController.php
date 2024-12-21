<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\admin\settings\bussiness_setup\CompanyRequest;
use Illuminate\Http\Request;
use App\trait\image;

use App\Models\CompanyInfo;
use App\Models\Currency;

class CompanyController extends Controller
{
    public function __construct(private CompanyInfo $company_info, 
    private Currency $currency){}
    use image;
    
    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/company
        $company_info = $this->company_info
        ->orderByDesc('id')
        ->first();
        $currency = $this->currency
        ->select('id', 'currancy_name')
        ->get();

        return response()->json([
            'company_info' => $company_info,
            'currency' => $currency,
        ]);
    }

    public function add(CompanyRequest $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/company/add
        // Keys
        // name, phone, email, address, logo, fav_icon, time_zone, time_format => [24hours,am/pm],
        // currency_id, currency_position => [left,right], copy_right, logo, fav_icon
        $companyRequest = $request->validated(); 
        $company_info = $this->company_info
        ->orderByDesc('id')
        ->first();
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

        return response()->json([
            'success' => $company_info
        ]);
    }
}
