<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Purchase;
use App\Models\PurchaseCategory;
use App\Models\PurchaseProduct;
use App\Models\PurchaseStore;
use App\Models\Admin;

class PurchaseController extends Controller
{  
    public function __construct(private Purchase $purchases,
    private PurchaseProduct $products, private PurchaseCategory $categories,
    private PurchaseStore $stores, private Admin $admin){}
    use image;

    public function view(Request $request){
        $purchases = $this->purchases
        ->with('category', 'product', 'admin', 'store')
        ->get()
        ->map(function($item){
            return [
                'total_coast' => $item->total_coast,
                'quintity' => $item->quintity,
                'date' => $item->date,
                'receipt_link' => $item->receipt_link,
                'category_id' => $item->category_id,
                'product_id' => $item->product_id,
                'admin_id' => $item->admin_id,
                'store_id' => $item->store_id,
                'category' => $item?->category?->name,
                'product' => $item?->product?->name,
                'admin' => $item?->admin?->name,
                'store' => $item?->store?->name,
            ];
        });
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();
        $products = $this->products
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();
        $stores = $this->stores
        ->select('id', 'name')
        ->where('status', 1)
        ->get();
        $admins = $this->admin
        ->select('id', 'name')
        ->where('status', 1)
        ->get();

        return response()->json([
            'purchases' => $purchases,
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores,
            'admins' => $admins,
        ]);
    }

    public function purchase_item(Request $request, $id){
        $purchases = $this->purchases
        ->with('category', 'product', 'admin', 'store')
        ->where('id', $id)
        ->first();

        return response()->json([
            'total_coast' => $purchases->total_coast,
            'quintity' => $purchases->quintity,
            'date' => $purchases->date,
            'receipt_link' => $purchases->receipt_link,
            'category_id' => $purchases->category_id,
            'product_id' => $purchases->product_id,
            'admin_id' => $purchases->admin_id,
            'store_id' => $purchases->store_id,
            'category' => $purchases?->category?->name,
            'product' => $purchases?->product?->name,
            'admin' => $purchases?->admin?->name,
            'store' => $purchases?->store?->name,
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'product_id' => ['required', 'exists:purchase_products,id'],
            'store_id' => ['required', 'exists:purchase_stores,id'],
            'total_coast' => ['required', 'numeric'],
            'quintity' => ['required', 'numeric'],
            'receipt' => ['required'],
            'date' => ['required', 'date'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }// 

        $purchaseRequest = $validator->validated();
        $purchaseRequest['admin_id'] = $request->user()->id;
        if (!empty($request->receipt)) {
            $imag_path = $this->upload($request, 'receipt', 'admin/purchases/receipt');
            $purchaseRequest['receipt'] = $imag_path;
        }
        $this->purchases
        ->create($purchaseRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'product_id' => ['required', 'exists:purchase_products,id'],
            'store_id' => ['required', 'exists:purchase_stores,id'],
            'total_coast' => ['required', 'numeric'],
            'quintity' => ['required', 'numeric'],
            'date' => ['required', 'date'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }// 

        $purchases = $this->purchases
        ->where('id', $id)
        ->first();
        if(empty($purchases)){
            return response()->json([
                'errors' => 'id is wrong'
            ], 400);
        }
        $purchaseRequest = $validator->validated();
        $purchaseRequest['admin_id'] = $request->user()->id;
        if (!empty($request->receipt)) {
            $imag_path = $this->upload($request, 'receipt', 'admin/purchases/receipt');
            $purchaseRequest['receipt'] = $imag_path;
            $this->deleteImage($purchases->receipt);
        }
        $purchases
        ->update($purchaseRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function delete(Request $request, $id){
        $purchases = $this->purchases
        ->where('id', $id)
        ->first();
        if(empty($purchases)){
            return response()->json([
                'errors' => 'id is wrong'
            ], 400);
        }
        $this->deleteImage($purchases->receipt);
        $purchases->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
