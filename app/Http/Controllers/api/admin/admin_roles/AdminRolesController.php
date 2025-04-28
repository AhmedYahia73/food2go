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
            'Admin' => ['all', 'view', 'add', 'edit', 'delete'],
            'Home' => ['all', 'view'],
            'Addons' => ['all', 'view', 'add', 'edit', 'delete'],
            'Banner' => ['all', 'view', 'add', 'edit', 'delete'],
            'AdminRoles' => ['all', 'view', 'add', 'edit', 'delete'],
            'Category' => ['all', 'view', 'add', 'edit', 'delete'],
            'Coupon' => ['all', 'view', 'add', 'edit', 'delete'],
            'Customer' => ['all', 'view', 'add', 'edit', 'delete'],
            'Deal' => ['all', 'view', 'add', 'edit', 'delete'],
            'Delivery' => ['all', 'view', 'add', 'edit', 'delete'],
            'Product' => ['all', 'view', 'add', 'edit', 'delete'],
            'PointOffers' => ['all', 'view', 'add', 'edit', 'delete'],
            'Branch' => ['all', 'view', 'add', 'edit', 'delete', 'product', 'category', 'option'],
            'Cashier' => ['all', 'view', 'add', 'edit', 'delete'],
            'CashierMan' => ['all', 'view', 'add', 'edit', 'delete'],
            'Kitchen' => ['all', 'view', 'add', 'edit', 'delete'],
            'Captain' => ['all', 'view', 'add', 'edit', 'delete'],
            'Translation' => ['all', 'view', 'add', 'edit', 'delete'],
            'CafeLocation' => ['all', 'view', 'add', 'edit', 'delete'],
            'CafeTable' => ['all', 'view', 'add', 'edit', 'delete'],
            'PosCustomer' => ['all', 'view', 'add', 'edit'],
            'PosAddress' => ['all', 'view', 'add', 'edit'],
            'Extra' => ['all', 'view', 'add', 'edit', 'delete'],
            'Zone' => ['all', 'view', 'add', 'edit', 'delete'],
            'City' => ['all', 'view', 'add', 'edit', 'delete'],
            'Tax' => ['all', 'view', 'add', 'edit', 'delete'],
            'Discount' => ['all', 'view', 'add', 'edit', 'delete'],
            'PaymentMethod' => ['all', 'view', 'add', 'edit', 'delete'],
            'FinancialAccounting' => ['all', 'view', 'add', 'edit', 'delete'],
            'Menue' => ['all', 'view', 'add', 'status', 'delete'],
            'DealOrder' => ['all', 'view', 'add'], 
            'OfferOrder' => ['all', 'approve_offer'], 
            'Order' => ['all', 'log', 'view', 'status', 'back_status'], 
            'OrderDelay' => ['all', 'view', 'add', 'edit', 'delete'], 
            'Payments' => ['all', 'view', 'status'],
            'PosReports' => ['all', 'view'], 
            'PosOrder' => ['all', 'view'], 
            'PosTable' => ['all', 'status'], 
            'OrderType' => ['all', 'view', 'edit'], 
            'PaymentMethodAuto' => ['all', 'view', 'edit', 'status'], 
            'CompanyInfo' => ['all', 'view', 'edit'], 
            'Maintenance' => ['all', 'view', 'add'], 
            'MainBranch' => ['all', 'view', 'edit'], 
            'TimeSlot' => ['all', 'view', 'edit'],
            'CustomerLogin' => ['all', 'view', 'edit'],
            'OrderSettings' => ['all', 'view', 'edit'],
            'TimeCancel' => ['all', 'view', 'edit'],
            'ResturantTime' => ['all', 'view', 'edit'],
            'TaxType' => ['all', 'view', 'edit'],
            'DeliveryTime' => ['all', 'view', 'edit'],
            'PreparingTime' => ['all', 'view', 'edit'],
            'NotificationSound' => ['all', 'view', 'edit'],
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
