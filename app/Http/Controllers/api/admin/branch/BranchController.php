<?php

namespace App\Http\Controllers\api\admin\branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\branch\BranchRequest;
use App\Http\Requests\admin\branch\UpdateBranchRequest;
use App\trait\image;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Branch;

class BranchController extends Controller
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
        'coverage',
        'status',
    ];
    use image;

    public function view(){
        // https://backend.food2go.pro/admin/branch
        $branches = $this->branches->get();

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function status(Request $request, $id){
        // https://backend.food2go.pro/admin/branch/status/{id}
        // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->branches->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        if ($request->status == 0) {
            return response()->json([
                'success' => 'banned'
            ]);
        } else {
            return response()->json([
                'success' => 'active'
            ]);
        }
    }
    
    public function create(BranchRequest $request){
        // https://backend.food2go.pro/admin/branch/add
        // Keys
        // name, address, email, phone, password, food_preparion_time, latitude, longitude
        // coverage, status, image, cover_image
  
        $branchRequest = $request->only($this->branchRequest);
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $branchRequest['image'] = $imag_path; 
        }
        if (is_file($request->cover_image)) {
            $imag_path = $this->upload($request, 'cover_image', 'users/branch/cover_image');
            $branchRequest['cover_image'] = $imag_path; 
        }
        $this->branches->create($branchRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }
    
    public function modify(UpdateBranchRequest $request, $id){
        // https://backend.food2go.pro/admin/branch/update/{id}
        // Keys
        // name, address, email, phone, password, food_preparion_time, latitude, longitude
        // coverage, status, image, cover_image

        $branchRequest = $request->only($this->branchRequest);
        $branch = $this->branches
        ->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $branchRequest['image'] = $imag_path;
            $this->deleteImage($branch->image);
        }
        if (is_file($request->cover_image)) {
            $imag_path = $this->upload($request, 'cover_image', 'users/branch/cover_image');
            $branchRequest['cover_image'] = $imag_path;
            $this->deleteImage($branch->cover_image);
        }
        $branch->update($branchRequest);

        return response()->json([
            'success' => 'You update data success'
        ]); 
    }
    
    public function delete($id){
        // https://backend.food2go.pro/admin/branch/delete/{id}
        $branch = $this->branches
        ->where('id', $id)
        ->first();
        $this->deleteImage($branch->image);
        $this->deleteImage($branch->cover_image);
        $branch->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
    
}
