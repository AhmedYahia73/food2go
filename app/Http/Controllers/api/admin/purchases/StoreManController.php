<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\StorageMan;
use App\Models\PurchaseStore;

class StoreManController extends Controller
{
    public function __construct(private StorageMan $store_man,
    private PurchaseStore $stores){}
    use image;

    public function view(Request $request){
        $store_man = $this->store_man
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'user_name' => $item->user_name,
                'phone' => $item->phone,  
                'store' => [
                    'id' => $item?->store_id,
                    'name' => $item?->store?->name,
                ],
                'image' => $item->image_link,
                'status' => $item->status,
            ];
        });
        $stores = $this->stores
        ->select('id', 'name')
        ->where('status', 1)
        ->get();


        return response()->json([
            'store_men' => $store_man,
            'stores' => $stores,
        ]);
    }
    
    public function store_man(Request $request, $id){ 
        $store_man = $this->store_man
        ->where('store_man_id', $id)
        ->get()
        ->map(function($item){
            return [
                'user_name' => $item->user_name,
                'phone' => $item->phone, 
                'store_id' => $item?->store_id,
                'stora' => $item?->store?->name,
                'image' => $item->image_link,
            ];
        });

        return response()->json([
            'store_man' => $store_man,
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->store_man
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'user_name' => ['required'],
            'phone' => ['required', 'unique:storage_men,phone'],
            'password' => ['required'], 
            'status' => ['required', 'boolean'],
            'store_id' => ['required', 'exists:purchase_stores,id'],
            // image
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $store_manRequest = $validator->validated();
        if(!empty($request->image)){
            $imag_path = $this->upload($request, 'image', 'admin/store_man/image');
            $store_manRequest['image'] = $imag_path;
        }
        $store_man = $this->store_man
        ->create($store_manRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'user_name' => ['required'],
            'phone' => ['required', 'unique:storage_men,phone,' . $id],
            'status' => ['required', 'boolean'],
            'store_id' => ['required', 'exists:purchase_stores,id'],
            // image, password
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $store_manRequest = $validator->validated();
        $store_man = $this->store_man
        ->where('id', $id)
        ->first();
        if(!empty($request->image)){
            $imag_path = $this->upload($request, 'image', 'admin/store_man/image');
            $store_manRequest['image'] = $imag_path;
            $this->deleteImage($purchases->image);
        }
        if(!empty($request->password)){
            $store_manRequest['password'] = bcrypt($request->password);
        }
        $store_man->update($store_manRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        $this->store_man
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
