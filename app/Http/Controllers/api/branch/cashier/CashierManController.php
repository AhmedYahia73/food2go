<?php

namespace App\Http\Controllers\api\branch\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\cashier_man\CasheirManRequest;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Cashier;
use App\Models\CashierMan;
use App\Models\CashierRole;
use App\Models\PersonalAccessToken;
use App\Models\Branch;

class CashierManController extends Controller
{
    public function __construct(private Cashier $cashier,
    private CashierMan $cashier_men, private Branch $branch,
    private CashierRole $cashier_role){}
    use image;

    public function view(Request $request){
        // /admin/cashier_man
        $cashier = $this->cashier
        ->where("branch_id", $request->user()->id)
        ->get();
        $cashier_men = $this->cashier_men
        ->where("branch_id", $request->user()->id)
        ->with('branch', 'roles')
        ->get();
        $roles = [
            'branch_reports',
            'all_reports',
            'table_status',
        ];

        return response()->json([
            'cashiers' => $cashier,
            'cashier_men' => $cashier_men,
            'roles' => $roles,
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
        ->where("branch_id", $request->user()->id)
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
        ->where("branch_id", $request->user()->id)
        ->with('branch', 'roles')
        ->where('id', $id)
        ->first();

        return response()->json([
            'cashier_man' => $cashier_man,
        ]);
    }

    public function create(Request $request){
        // admin/cashier_man/add
        // Keys
        // user_name, password, branch_id, status, my_id
        // take_away, dine_in, delivery, car_slow, image,
        // roles[]
        $validation = Validator::make($request->all(), [
            'roles.*' => ['in:branch_reports,all_reports,table_status'],
            'password' => ['required'],
            'user_name' => ['required'],
            'status' => ['required', 'boolean'],
            'take_away' => ['required', 'boolean'],
            'dine_in' => ['required', 'boolean'],
            'delivery' => ['required', 'boolean'],
            'car_slow' => ['required', 'boolean'],
            'my_id' => ['required'],
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $validation->validated(); 
        $cashierRequest = collect($cashierRequest)->only([
            'password', 'user_name', 'status', 'take_away', 
            'dine_in', 'delivery', 'car_slow', 'my_id'
        ])->toArray();
        $cashierRequest['password'] = $request->password;
        $cashierRequest['branch_id'] = $request->user()->id;
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

    public function modify(Request $request, $id){
        // admin/cashier_man/update/{id}
        // user_name, password, branch_id, status, my_id
        // take_away, dine_in, delivery, car_slow, image,
        // roles[]
        $validation = Validator::make($request->all(), [
            'roles.*' => ['in:branch_reports,all_reports,table_status'],
            'user_name' => ['required'],
            'status' => ['required', 'boolean'],
            'take_away' => ['required', 'boolean'],
            'dine_in' => ['required', 'boolean'],
            'delivery' => ['required', 'boolean'],
            'car_slow' => ['required', 'boolean'],
            'my_id' => ['required'],
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $cashierRequest = $validation->validated(); 
        $cashierRequest = collect($cashierRequest)->only([
            'password', 'user_name', 'status', 'take_away', 
            'dine_in', 'delivery', 'car_slow', 'my_id'
        ])->toArray();
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
