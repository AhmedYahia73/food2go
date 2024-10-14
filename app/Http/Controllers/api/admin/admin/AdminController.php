<?php

namespace App\Http\Controllers\api\admin\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\admin\AdminRequest;
use App\Http\Requests\admin\admin\UpdateAdminRequest;
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
        'password',
        'user_position_id',
        'status',
    ];
    use image;

    public function view(){
        $admins = $this->admins
        ->with('user_positions')->get();
        $user_positions = $this->user_positions->get();

        return response()->json([
            'admins' => $admins,
            'user_positions' => $user_positions
        ]);
    }
    
    public function create(AdminRequest $request){
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
        $this->admins->create($adminRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }
    
    public function modify(UpdateAdminRequest $request, $id){
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
        $admin->update($adminRequest);

        return response()->json([
            'success' => 'You update data success'
        ]); 
    }
    
    public function delete($id){
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
