<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\bussiness_setup\MaintenanceRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Maintenance;

class MaintenanceController extends Controller
{
    public function __construct(private Maintenance $maintenance){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/maintenance
        $maintenance = $this->maintenance
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'maintenance' => $maintenance
        ]);
    }

    public function status(Request $request){      
        // https://bcknd.food2go.online/admin/settings/business_setup/maintenance/status
        // Key
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $maintenance = $this->maintenance 
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function add(MaintenanceRequest $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/maintenance/add
        // Keys
        // all, branch, customer, web, delivery, day, week, until_change, customize,
        // start_date, end_date, status
        $maintenanceRequest = $request->validated();
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
        
        return response()->json([
            'maintenance' => $maintenance
        ]);
    }
}
