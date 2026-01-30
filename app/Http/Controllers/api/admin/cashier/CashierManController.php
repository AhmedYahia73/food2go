<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\cashier_man\CasheirManRequest;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Cashier;
use App\Models\CashierMan;
use App\Models\CashierShift;
use App\Models\CashierRole;
use App\Models\PersonalAccessToken;
use App\Models\Branch;

class CashierManController extends Controller
{
    public function __construct(private Cashier $cashier,
    private CashierMan $cashier_men, private Branch $branch,
    private CashierRole $cashier_role, private CashierShift $shift){}
    use image;

    public function view(Request $request){
        // /admin/cashier_man
        $cashier = $this->cashier
        ->get();
        $cashier_men = $this->cashier_men
        ->with('branch:id,name', 'roles', "cashier:id,name")
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'user_name' => $item->user_name,
                'branch' => $item->branch,
                'delivery' => $item->delivery,
                'image_link' => $item->image_link,
                'shift_number' => $item->shift_number,
                'take_away' => $item->take_away,
                'dine_in' => $item->dine_in,
                'delivery' => $item->delivery,
                'void_order' => $item->void_order,
                'real_order' => $item->real_order,
                'my_id' => $item->my_id,
                'status' => $item->status,
                'manger' => $item->manger,
                'cashier' => $item->cashier,
                'login' => $item->tokens()->exists(),
                'discount_perimission' => $item->discount_perimission,
                'online_order' => $item->online_order,
                'report' => $item->report,
                "free_discount" => $item->free_discount,
                'service_fees'=> $item->service_fees,
                'total_tax'=> $item->total_tax,
                'enter_amount'=> $item->enter_amount,
                'hall_orders'=> $item->hall_orders,
            ];
        });
        $branches = $this->branch
        ->where('status', 1)
        ->get();
        $roles = [
            'branch_reports',
            'all_reports',
            'table_status',
        ];
        $report_role = [
            "unactive", 
            "financial", 
            "all"
        ];

        return response()->json([
            'cashiers' => $cashier,
            'cashier_men' => $cashier_men,
            'branches' => $branches,
            'roles' => $roles,
            'report_role' => $report_role,
        ]);
    }

    public function logout_cashier(Request $request, $id){
        $cashier_man = $this->cashier_men
        ->where("id", $id)
        ->first();
        if(!$cashier_man){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $this->shift
        ->where("cashier_man_id", $id)
        ->whereNull("end_time")
        ->update([
            "end_time" => now()
        ]);
        $cashier_man->tokens()->delete();

        return response()->json([
            "success" => "You logout cashier man success"
        ]);
    }

    public function status(Request $request, $id){
        // admin/cashier_man/status/{id}
        // Keys
        // status
        $validation = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashier_men = $this->cashier_men
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]); 

        return response()->json([
            'success' => $request->status ? 'active' : 'banned',
        ]);
    }
    
    public function cashier_man(Request $request, $id){
        // /admin/cashier_man/item/{id}
        $cashier_man = $this->cashier_men
        ->with('branch', 'roles')
        ->where('id', $id)
        ->first();

        return response()->json([
            'cashier_man' => $cashier_man,
        ]);
    }

    public function create(CasheirManRequest $request){
        // admin/cashier_man/add
        // Keys
        // user_name, password, branch_id, status, my_id
        // take_away, dine_in, delivery, car_slow, image, dicount_id
        // roles[]
        $validation = Validator::make($request->all(), [
            'roles.*' => ['in:branch_reports,all_reports,table_status'],
            'password' => ['required'],
            'branch_id' => ['exists:branches,id'],
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $request->validated();
        $cashierRequest['branch_id'] = $request->branch_id; 
        $cashierRequest['password'] = $request->password;
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/cashier/image');
            $cashierRequest['image'] = $imag_path;
        }
        $cashier_men = $this->cashier_men
        ->create($cashierRequest);
        if ($request->roles) {
            foreach ($request->roles as $item) {
                $this->cashier_role
                ->create([
                    'roles' => $item,
                    'cashier_man_id' => $cashier_men->id,
                ]);
            }
        }

        return response()->json([
            'success' => $cashier_men,
        ]);
    }

    public function modify(CasheirManRequest $request, $id){
        // admin/cashier_man/update/{id}
        // user_name, password, branch_id, status, my_id
        // take_away, dine_in, delivery, car_slow, image,
        // roles[]
        $validation = Validator::make($request->all(), [
            'roles.*' => ['in:branch_reports,all_reports,table_status'],
            'password' => ['nullable', 'min:8'],
            'branch_id' => ['exists:branches,id'],
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $request->validated();
        $cashierRequest['branch_id'] = $request->branch_id; 
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/cashier/image');
            $cashierRequest['image'] = $imag_path;
        }
        $cashier_men = $this->cashier_men
        ->where('id', $id)
        ->first();
        if (empty($cashier_men)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        if (!empty($request->password)) {
            $cashierRequest['password'] = bcrypt($request->password);
            PersonalAccessToken::
            where('name', 'cashier')
            ->where('tokenable_id', $id)
            ->delete();
        }
        $cashier_men->update($cashierRequest);
        $this->cashier_role
        ->where('cashier_man_id', $id)
        ->delete();
        if ($request->roles) {
            foreach ($request->roles as $item) {
                $this->cashier_role
                ->create([
                    'roles' => $item,
                    'cashier_man_id' => $id,
                ]);
            }
        }

        return response()->json([
            'success' => $cashier_men,
        ]);
    }

    public function delete(Request $request, $id){
        // admin/cashier_man/delete/{id}   
        $cashier = $this->cashier_men
        ->where('id', $id)
        ->first();
        if (empty($cashier)) {
            return response()->json([
                'errors' => 'cashier is not found'
            ], 400);
        }
        $cashier->delete();

        return response()->json([
            'success' => 'You delete cashier success'
        ], 200);
    }
}
