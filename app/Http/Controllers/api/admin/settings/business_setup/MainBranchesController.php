<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\branch\BranchRequest;
use App\Http\Requests\admin\branch\UpdateBranchRequest;
use App\trait\image;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Branch;

class MainBranchesController extends Controller
{
    public function __construct(private Branch $branches){}
    protected $branchRequest = [
        'name',
        'address',
        'email',
        'phone',
        'password',
        'food_preparion_time',
        'latitude',
        'longitude',
        'city_id',
        'coverage',
        'status',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/branch
        $branches = $this->branches
        ->where('main', 1)
        ->with('city')
        ->first();

        return response()->json([
            'branches' => $branches,
        ]);
    }
    
    public function update(BranchRequest $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/branch/add 
        // Keys
        // name, address, email, phone, password, food_preparion_time, latitude, longitude
        // coverage, status, image, cover_image, city_id
  
        $branchRequest = $request->only($this->branchRequest);
        $branchRequest['main'] = 1;
        if ($request->image && !is_string($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $branchRequest['image'] = $imag_path; 
        }
        if ($request->cover_image && !is_string($request->cover_image)) {
            $imag_path = $this->upload($request, 'cover_image', 'users/branch/cover_image');
            $branchRequest['cover_image'] = $imag_path; 
        }
        $branches = $this->branches
        ->where('main', 1)
        ->with('city')
        ->first();

        if (empty($branches)) {
            $this->branches->create($branchRequest);
        } 
        else {
            $branches->update($branchRequest);
        }
        
        return response()->json([
            'success' => 'You make proccess success'
        ]);
    }
}
