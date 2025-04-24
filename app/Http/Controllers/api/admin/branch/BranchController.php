<?php

namespace App\Http\Controllers\api\admin\branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\branch\BranchRequest;
use App\Http\Requests\admin\branch\UpdateBranchRequest;
use App\trait\image;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariationProduct;
use App\Models\BranchOff;

class BranchController extends Controller
{
    public function __construct(private Branch $branches, private Category $categories
    , private Product $products, private BranchOff $branch_off,
    private VariationProduct $variations){}
    protected $branchRequest = [
        'name',
        'address',
        'email',
        'phone',
        'password',
        'food_preparion_time',
        'latitude',
        'longitude',
        'city_id',
        'coverage',
        'status',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/branch
        $branches = $this->branches
        ->with('city')
        ->get();

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function branch($id){
        // https://bcknd.food2go.online/admin/branch/item/{id}
        $branch = $this->branches
        ->where('id', $id)
        ->with('city')
        ->first();

        return response()->json([
            'branch' => $branch,
        ]);
    }

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/branch/status/{id}
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

        $this->branches->where('id', $id)
        ->where('main', '!=', 1)
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
    
    public function create(BranchRequest $request){
        // https://bcknd.food2go.online/admin/branch/add
        // Keys
        // name, address, email, phone, password, food_preparion_time, latitude, longitude
        // coverage, status, image, cover_image, city_id
  
        $branchRequest = $request->only($this->branchRequest);
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $branchRequest['image'] = $imag_path; 
        }
        if (is_file($request->cover_image)) {
            $imag_path = $this->upload($request, 'cover_image', 'users/branch/cover_image');
            $branchRequest['cover_image'] = $imag_path; 
        }
        $this->branches->create($branchRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }
    
    public function modify(UpdateBranchRequest $request, $id){
        // https://bcknd.food2go.online/admin/branch/update/{id}
        // Keys
        // name, address, email, phone, password, food_preparion_time, latitude, longitude
        // coverage, status, image, cover_image, city_id

        $branchRequest = $request->only($this->branchRequest);
        $branch = $this->branches
        ->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/branch/image');
            $branchRequest['image'] = $imag_path;
            $this->deleteImage($branch->image);
        }
        if (is_file($request->cover_image)) {
            $imag_path = $this->upload($request, 'cover_image', 'users/branch/cover_image');
            $branchRequest['cover_image'] = $imag_path;
            $this->deleteImage($branch->cover_image);
        }
        $branch->update($branchRequest);

        return response()->json([
            'success' => 'You update data success'
        ]); 
    }
    
    public function delete($id){
        // https://bcknd.food2go.online/admin/branch/delete/{id}
        $branch = $this->branches
        ->where('id', $id)
        ->where('main', '!=', 1)
        ->first();
        $this->deleteImage($branch->image);
        $this->deleteImage($branch->cover_image);
        $branch->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }

    public function branch_in_product($product_id){
        // /admin/branch/branch_in_product/{product_id}
        $product = $this->products
        ->where('id', $product_id)
        ->first();
        $branch_off_product = $this->branch_off
        ->where('product_id', $product_id) 
        ->get();
        $branch_off_category = $this->branch_off 
        ->orWhere('category_id', $product->category_id)
        ->orWhere('category_id', $product->sub_category_id)
        ->whereNotNull('category_id')
        ->get();
        $branches = $this->branches->where('status', 1)
        ->get()
        ->map(function($item) use($branch_off_product, $branch_off_category) {
            $branches_product_off = $branch_off_product->pluck('branch_id')->filter();
            $branches_category_off = $branch_off_category->pluck('branch_id')->filter(); 
            if ( $branches_category_off->contains($item->id) || $branches_product_off->contains($item->id)) {
                $item->product_status = 0;
            }
            else{
                $item->product_status = 1;
            }
            return $item;
        });

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function branch_product($id){
        // /admin/branch/branch_product/{id}
        $branch_off = $this->branch_off
        ->where('branch_id', $id)
        ->get();
        $products = $this->products
        ->where('status', 1)
        ->get()
        ->map(function($item) use($branch_off) {
            $product_off = $branch_off->pluck('product_id')->filter()->toArray();
            $category_off = $branch_off->pluck('category_id')->filter()->toArray();
            if (in_array($item->id, $product_off)) {
                $item->status = 0;
            }
            if (in_array($item->category_id, $category_off) || in_array($item->sub_category_id, $category_off)) {
                $item->status = 0;
            }
            return $item;
        });
        $categories = $this->categories
        ->where('status', 1)
        ->get()
        ->map(function($item) use($branch_off) {
            $category_off = $branch_off->pluck('category_id')->toArray();
            if (in_array($item->id, $category_off)) {
                $item->status = 0;
            }
            return $item;
        });

        return response()->json([
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function branch_options($id){
        // /admin/branch/branch_options/{id}
        $branch_off = $this->branch_off
        ->where('branch_id', $id)
        ->get();
        $option_off = $branch_off->pluck('option_id')->filter()->toArray();
        $variations = $this->variations
        ->where('product_id', $id)
        ->with(['options' => function($query){
            $query->where('status', 1);
        }])
        ->get()
        ->map(function($item) use($option_off) {
            $item->options->map(function($element) use($option_off) {
                if (in_array($element->id, $option_off)) {
                    $element->status = 0;
                }
                return $element;
            });
            return $item;
        });

        return response()->json([
            'variations' => $variations,
        ]);
    }

    public function branch_product_status(Request $request, $id){
        // /admin/branch/branch_product_status/{id}
        // keys
        // status, branch_id
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        if ($request->status) {
            $branch_off = $this->branch_off
            ->where('branch_id', $request->branch_id)
            ->where('product_id', $id)
            ->delete();
        } 
        else {
            $this->branch_off
            ->create([
                'branch_id' => $request->branch_id,
                'product_id' => $id
            ]);
        }
        
        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    public function branch_category_status(Request $request, $id){
        // /admin/branch/branch_category_status/{id}
        // keys
        // status, branch_id
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        if ($request->status) {
            $branch_off = $this->branch_off
            ->where('branch_id', $request->branch_id)
            ->where('category_id', $id)
            ->delete();
        } 
        else {
            $this->branch_off
            ->create([
                'branch_id' => $request->branch_id,
                'category_id' => $id
            ]);
        }
        
        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    public function branch_option_status(Request $request, $id){
        // /admin/branch/branch_option_status/{id}
        // keys
        // status, branch_id
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        if ($request->status) {
            $branch_off = $this->branch_off
            ->where('branch_id', $request->branch_id)
            ->where('option_id', $id)
            ->delete();
        } 
        else {
            $this->branch_off
            ->create([
                'branch_id' => $request->branch_id,
                'option_id' => $id
            ]);
        }
        
        return response()->json([
            'success' => 'You change status success'
        ]);
    }
}
