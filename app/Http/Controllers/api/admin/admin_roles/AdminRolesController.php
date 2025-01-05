<?php

namespace App\Http\Controllers\api\admin\admin_roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\admin_roles\AdminRoleRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\UserPosition;
use App\Models\UserRole;

class AdminRolesController extends Controller
{
    public function __construct(private UserPosition $user_positions, 
    private UserRole $user_roles){}
    protected $roleRequest = [
        'name',
        'status'
    ];

    public function view(){
        // https://bcknd.food2go.online/admin/admin_roles
        $user_positions = $this->user_positions
        ->with('roles')
        ->get();
        $roles = ['Admin', 'Addons', 'AdminRoles', 'Banner',
        'Branch', 'Category', 'Coupon', 'Customer', 'Deal', 
        'DealOrder', 'Delivery', 'OfferOrder', 'Order', 
        'Payments', 'PointOffers', 'Product', 'Settings', 'Home'];

        return response()->json([
            'user_positions' => $user_positions,
            'roles' => $roles,
        ]);
    }

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/admin_roles/status/{id}
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
        $this->user_positions
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' =>  $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(AdminRoleRequest $request){
        // https://bcknd.food2go.online/admin/admin_roles/add
        // Keys
        // name, status, roles[]
        $roleRequest = $request->only($this->roleRequest);
        $user_positions = $this->user_positions
        ->create($roleRequest);
        if ($request->roles) {
            $roles = json_decode($request->roles) ?? [];
            foreach ($roles as $role) {
                $this->user_roles
                ->create([
                    'user_position_id' => $user_positions->id,
                    'role' => $role,
                ]);
            }
        }

        return response()->json([
            'sucess' => 'You add data success'
        ]);
    }

    public function modify(AdminRoleRequest $request, $id){
        // https://bcknd.food2go.online/admin/admin_roles/update/{id}
        // Keys
        // name, status, roles[]
        $roleRequest = $request->only($this->roleRequest);
        $user_positions = $this->user_positions
        ->where('id', $id)
        ->update($roleRequest);
        $this->user_roles
        ->where('user_position_id', $id)
        ->delete();
        if ($request->roles) {
            $roles = json_decode($request->roles) ?? [];
            foreach ($roles as $role) {
                $this->user_roles
                ->create([
                    'user_position_id' => $id,
                    'role' => $role,
                ]);
            }
        }

        return response()->json([
            'sucess' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/admin_roles/delete/{id}
        $user_positions = $this->user_positions
        ->where('id', $id)
        ->delete();

        return response()->json([
            'sucess' => 'You delete data success'
        ]);
    }
}
