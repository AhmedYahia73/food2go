<?php

namespace App\Http\Controllers\api\admin\main_data;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\MainData;
use App\Models\Policy;

class MainDataController extends Controller
{
    public function __construct(private MainData $main_data, private Policy $policy){}
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/settings/main_data
        $main_data = $this->main_data
        ->orderByDesc('id')
        ->first();
        if (!empty($main_data)) {
            $main_data->ar_name = $main_data->translations()
            ->where('locale', 'ar')->where('key', $main_data->name)
            ->first()?->value ?? null;
        }

        return response()->json([
            'main_data' => $main_data
        ]);
    }

    public function update(Request $request){
        // https://bcknd.food2go.online/admin/settings/main_data/update
        //Key
        // name, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'ar_name' => 'required',
            'logo' => 'required',
            "continues_status"  => "required|boolean",
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $main_data = $this->main_data
        ->orderByDesc('id')
        ->first();
        $dataRequest = [
            'name' => $request->name,
            'logo' => $request->logo,
            'first_color' => $request->first_color ?? null,
            'second_color' => $request->second_color ?? null,
            'third_color' => $request->third_color ?? null,
            'instagram' => $request->instagram ?? null,
            'facebook' => $request->facebook ?? null,
            "continues_status" => $request->continues_status ?? 1,
        ];
        if (empty($main_data)) {
            if (!empty($request->logo) && !is_string($request->logo)) {
                $imag_path = $this->upload($request, 'logo', 'admin/main_data/image');
                $dataRequest['logo'] = $imag_path;
            }
            else{
                $dataRequest['logo'] = null;
            }
            if (!empty($request->image_1) && !is_string($request->image_1)) {
                $imag_path = $this->upload($request, 'image_1', 'admin/main_data/image');
                $dataRequest['image_1'] = $imag_path;
            }
            if (!empty($request->image_2) && !is_string($request->image_2)) {
                $imag_path = $this->upload($request, 'image_2', 'admin/main_data/image');
                $dataRequest['image_2'] = $imag_path;
            }
            if (!empty($request->image_3) && !is_string($request->image_3)) {
                $imag_path = $this->upload($request, 'image_3', 'admin/main_data/image');
                $dataRequest['image_3'] = $imag_path;
            }
            if (!empty($request->image_4) && !is_string($request->image_4)) {
                $imag_path = $this->upload($request, 'image_4', 'admin/main_data/image');
                $dataRequest['image_4'] = $imag_path;
            }
            if (!empty($request->image_5) && !is_string($request->image_5)) {
                $imag_path = $this->upload($request, 'image_5', 'admin/main_data/image');
                $dataRequest['image_5'] = $imag_path;
            }
            if (!empty($request->image_6) && !is_string($request->image_6)) {
                $imag_path = $this->upload($request, 'image_6', 'admin/main_data/image');
                $dataRequest['image_6'] = $imag_path;
            }
            $main_data = $this->main_data
            ->create($dataRequest);
        } 
        else {
            if (!empty($request->logo) && !is_string($request->logo)) {
                $imag_path = $this->update_image($request, $main_data->logo, 'logo', 'admin/main_data/image');
                $dataRequest['logo'] = $imag_path;
            }
            else{
                $dataRequest['logo'] = null;
            }
            if (!empty($request->image_1) && !is_string($request->image_1)) {
                $imag_path = $this->update_image($request, $main_data->image_1, 'image_1', 'admin/main_data/image');
                $dataRequest['image_1'] = $imag_path;
            }
            if (!empty($request->image_2) && !is_string($request->image_2)) {
                $imag_path = $this->update_image($request, $main_data->image_2, 'image_2', 'admin/main_data/image');
                $dataRequest['image_2'] = $imag_path;
            }
            if (!empty($request->image_3) && !is_string($request->image_3)) {
                $imag_path = $this->update_image($request, $main_data->image_3, 'image_3', 'admin/main_data/image');
                $dataRequest['image_3'] = $imag_path;
            }
            if (!empty($request->image_4) && !is_string($request->image_4)) {
                $imag_path = $this->update_image($request, $main_data->image_4, 'image_4', 'admin/main_data/image');
                $dataRequest['image_4'] = $imag_path;
            }
            if (!empty($request->image_5) && !is_string($request->image_5)) {
                $imag_path = $this->update_image($request, $main_data->image_5, 'image_5', 'admin/main_data/image');
                $dataRequest['image_5'] = $imag_path;
            }
            if (!empty($request->image_6) && !is_string($request->image_6)) {
                $imag_path = $this->update_image($request, $main_data->image_6, 'image_6', 'admin/main_data/image');
                $dataRequest['image_6'] = $imag_path;
            }
            $main_data->update($dataRequest);
            $main_data->translations()->delete();
        }
        $main_data->translations()->create([
            'locale' => 'ar',
            'key' => $request->name,
            'value' => $request->ar_name,
        ]);

        return response()->json([
            'success' => 'You update data success',
            'request' => $request->all(),
        ]);
    }
    

    public function view_policy(){
        // https://bcknd.food2go.online/admin/policy
        $data = $this->policy
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'data' => $data
        ]);
    }

    public function update_policy(Request $request){
        // https://bcknd.food2go.online/admin/policy/update
        //Key
        // policy, support
        $validator = Validator::make($request->all(), [
            'policy' => 'required',
            'support' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $data = $this->policy
        ->orderByDesc('id')
        ->first();
        $dataRequest = $validator->validated();
        if (empty($data)) { 
            $data = $this->policy
            ->create($dataRequest);
        } 
        else {
            $data->update($dataRequest);
        }

        return response()->json([
            'success' => 'You update data success',
            'request' => $request->all(),
        ]);
    }
}
