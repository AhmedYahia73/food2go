<?php

namespace App\Http\Controllers\api\admin\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\admin\AdminRequest;
use App\Http\Requests\admin\admin\UpdateAdminRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Admin;
use App\Models\UserPosition;

class AdminController extends Controller
{
    public function __construct(private Admin $admins, private UserPosition $user_positions){}
    protected $adminRequest = [
        'name',
        'identity_type',
        'identity_number',
        'email',
        'phone',
        'user_position_id',
        'status',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/admin
        $admins = $this->admins
        ->with('user_positions')
        ->where('id', '!=', auth()->user()->id)
        ->get();
        $user_positions = $this->user_positions
        ->where('status', 1)
        ->get();

        return response()->json([
            'admins' => $admins,
            'user_positions' => $user_positions
        ]);
    }

    public function admin($id){
        // https://bcknd.food2go.online/admin/admin/item/{id}
        $admin = $this->admins
        ->where('id', $id)
        ->with('user_positions')
        ->first();
        $user_positions = $this->user_positions
        ->where('status', 1)
        ->get();

        return response()->json([
            'admin' => $admin,
            'user_positions' => $user_positions
        ]);
    }

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/admin/status/{id}
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

        $this->admins->where('id', $id)
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
    
    public function create(AdminRequest $request){
        // https://bcknd.food2go.online/admin/admin/add
        // Keys
        // name, identity_type, identity_number, email, phone, password, user_position_id
        // status, image, identity_image
        $adminRequest = $request->only($this->adminRequest); 
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/admin/image');
            $adminRequest['image'] = $imag_path;
        }
        if (is_file($request->identity_image)) {
            $imag_path = $this->upload($request, 'identity_image', 'users/admin/identity_image');
            $adminRequest['identity_image'] = $imag_path;
        }
        $adminRequest['password'] = $request->password;
        $this->admins->create($adminRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }
    
    public function modify(UpdateAdminRequest $request, $id){
        // https://bcknd.food2go.online/admin/admin/update/{id}
        // Keys
        // name, identity_type, identity_number, email, phone, password, user_position_id
        // status, image, identity_image
        $adminRequest = $request->only($this->adminRequest);
        $admin = $this->admins->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/admin/image');
            $adminRequest['image'] = $imag_path;
            $this->deleteImage($admin->image);
        }
        if (is_file($request->identity_image)) {
            $imag_path = $this->upload($request, 'identity_image', 'users/admin/identity_image');
            $adminRequest['identity_image'] = $imag_path;
            $this->deleteImage($admin->identity_image);
        }
        if (!empty($request->password)) {
            $adminRequest['password'] = $request->password;
        }
        $admin->update($adminRequest);

        return response()->json([
            'success' => 'You update data success'
        ]); 
    }
    
    public function delete($id){
        // https://bcknd.food2go.online/admin/admin/delete/{id}
        $admin = $this->admins->where('id', $id)
        ->first();
        $this->deleteImage($admin->image);
        $this->deleteImage($admin->identity_image);
        $admin->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
