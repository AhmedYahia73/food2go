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
        ->get();

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function branch($id){
        // https://bcknd.food2go.online/admin/settings/business_setup/branch/item/{id} 
        $branch = $this->branches
        ->where('id', $id)
        ->with('city')
        ->first();

        return response()->json([
            'branch' => $branch,
        ]);
    }
    
    public function create(BranchRequest $request){
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
        $this->branches->create($branchRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }
    
    public function modify(UpdateBranchRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/business_setup/branch/update/{id}
        // Keys
        // name, address, email, phone, password, food_preparion_time, latitude, longitude
        // coverage, status, image, cover_image, city_id

        $branchRequest = $request->only($this->branchRequest);
        $branchRequest['main'] = 1;
        $branch = $this->branches
        ->where('id', $id)
        ->first();
        if ($request->image  && !is_string($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $branchRequest['image'] = $imag_path;
            $this->deleteImage($branch->image);
        }
        if ($request->cover_image && !is_string($request->cover_image)) {
            $imag_path = $this->upload($request, 'cover_image', 'users/branch/cover_image');
            $branchRequest['cover_image'] = $imag_path;
            $this->deleteImage($branch->cover_image);
        }
        $branch->update($branchRequest);

        return response()->json([
            'success' => 'You update data success'
        ]); 
    }
}
