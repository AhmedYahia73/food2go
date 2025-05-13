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
        'action',
        'status',
    ];

    public function view(){
        // https://bcknd.food2go.online/admin/admin_roles
        $user_positions = $this->user_positions
        ->with('roles')
        ->get();
        $roles = [
            'Admin' => [ 'view', 'add', 'edit', 'delete'],
            'Home' => [ 'view'],
            'Addons' => [ 'view', 'add', 'edit', 'delete'],
            'Banner' => [ 'view', 'add', 'edit', 'delete'],
            'AdminRoles' => [ 'view', 'add', 'edit', 'delete'],
            'Category' => [ 'view', 'add', 'edit', 'delete'],
            'Coupon' => [ 'view', 'add', 'edit', 'delete'],
            'Customer' => [ 'view', 'add', 'edit', 'delete'],
            'Deal' => [ 'view', 'add', 'edit', 'delete'],
            'Delivery' => [ 'view', 'add', 'edit', 'delete'],
            'Product' => [ 'view', 'add', 'edit', 'delete'],
            'PointOffers' => [ 'view', 'add', 'edit', 'delete'],
            'Branch' => [ 'view', 'add', 'edit', 'delete', 'product', 'category', 'option'],
            'Cashier' => [ 'view', 'add', 'edit', 'delete'],
            'CashierMan' => [ 'view', 'add', 'edit', 'delete'],
            'Kitchen' => [ 'view', 'add', 'edit', 'delete'],
            'Captain' => [ 'view', 'add', 'edit', 'delete'],
            'Translation' => [ 'view', 'add', 'edit', 'delete'],
            'CafeLocation' => [ 'view', 'add', 'edit', 'delete'],
            'CafeTable' => [ 'view', 'add', 'edit', 'delete'],
            'PosCustomer' => [ 'view', 'add', 'edit'],
            'PosAddress' => [ 'view', 'add', 'edit'],
            'Extra' => [ 'view', 'add', 'edit', 'delete'],
            'Zone' => [ 'view', 'add', 'edit', 'delete'],
            'City' => [ 'view', 'add', 'edit', 'delete'],
            'Tax' => [ 'view', 'add', 'edit', 'delete'],
            'Discount' => [ 'view', 'add', 'edit', 'delete'],
            'PaymentMethod' => [ 'view', 'add', 'edit', 'delete'],
            'FinancialAccounting' => [ 'view', 'add', 'edit', 'delete'],
            'Menue' => [ 'view', 'add', 'status', 'delete'],
            'DealOrder' => [ 'view', 'add'], 
            'OfferOrder' => [ 'approve_offer'], 
            'Order' => [ 'log', 'view', 'status', 'back_status'], 
            'OrderDelay' => [ 'view', 'add', 'edit', 'delete'], 
            'Payments' => [ 'view', 'status'],
            'PosReports' => [ 'view'], 
            'PosOrder' => [ 'view'], 
            'PosTable' => [ 'status'], 
            'OrderType' => [ 'view', 'edit'], 
            'PaymentMethodAuto' => [ 'view', 'edit', 'status'], 
            'CompanyInfo' => [ 'view', 'edit'], 
            'Maintenance' => [ 'view', 'add'], 
            'MainBranch' => [ 'view', 'edit'], 
            'TimeSlot' => [ 'view', 'edit'],
            'CustomerLogin' => [ 'view', 'edit'],
            'OrderSettings' => [ 'view', 'edit'],
            'TimeCancel' => [ 'view', 'edit'],
            'ResturantTime' => [ 'view', 'edit'],
            'TaxType' => [ 'view', 'edit'],
            'DeliveryTime' => [ 'view', 'edit'],
            'PreparingTime' => [ 'view', 'edit'],
            'NotificationSound' => [ 'view', 'edit'],
        ];

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
        // name, status, roles[role, action[all, view, add, edit, delete]]
        $roleRequest = $request->only($this->roleRequest);
        $user_positions = $this->user_positions
        ->create($roleRequest);
        if ($request->roles) {
            $roles = $request->roles;
            foreach ($roles as $role) {
                foreach ($role['action'] as $key => $action) {
                    $this->user_roles
                    ->create([
                        'user_position_id' => $user_positions->id,
                        'role' => $role['role'],
                        'action' => $action,
                    ]);
                }
            }
        }

        return response()->json([
            'sucess' => 'You add data success'
        ]);
    }

    public function modify(AdminRoleRequest $request, $id){
        // https://bcknd.food2go.online/admin/admin_roles/update/{id}
        // Keys
        // name, status, roles[role, action[all, view, add, edit, delete]]
        $roleRequest = $request->only($this->roleRequest);
        $user_positions = $this->user_positions
        ->where('id', $id)
        ->update($roleRequest);
        $this->user_roles
        ->where('user_position_id', $id)
        ->delete();
        if ($request->roles) {
            $roles = $request->roles;
            foreach ($roles as $role) {
                foreach ($role['action'] as $action) {
                    $this->user_roles
                    ->create([
                        'user_position_id' => $id,
                        'role' => $role['role'],
                        'action' => $action,
                    ]);
                }
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
