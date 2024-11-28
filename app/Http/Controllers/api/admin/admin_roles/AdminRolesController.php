<?php

namespace App\Http\Controllers\api\admin\admin_roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\admin_roles\AdminRoleRequest;

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
        $user_positions = $this->user_positions
        ->with('roles')
        ->get();
        $roles = ['Admin', 'Addons', 'AdminRoles', 'Banner',
        'Branch', 'Category', 'Coupon', 'Customer', 'Deal', 
        'DealOrder', 'Delivery', 'OfferOrder', 'Order', 
        'Payments', 'PointOffers', 'Product', 'Settings'];

        return response()->json([
            'user_positions' => $user_positions,
            'roles' => $roles,
        ]);
    }

    public function create(AdminRoleRequest $request){
        // Keys
        // name, status, roles[]
        $roleRequest = $request->only($this->roleRequest);
        $user_positions = $this->user_positions
        ->create($roleRequest);
        if ($request->roles) {
            foreach ($request->roles as $role) {
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
            foreach ($request->roles as $role) {
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
        $user_positions = $this->user_positions
        ->where('id', $id)
        ->delete();

        return response()->json([
            'sucess' => 'You delete data success'
        ]);
    }
}
