<?php

namespace App\Http\Controllers\api\admin\category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\category\CategoryRequest;
use App\trait\image;
use App\trait\translaion;

use App\Models\Category;
use App\Models\Addon;

class CreateCategoryController extends Controller
{
    public function __construct(private Category $categories, private Addon $addons){}
    protected $categoryRequest = [
        'name',
        'category_id',
        'status',
        'priority',
        'active',
    ];
    use image;
    use translaion;

    public function create(CategoryRequest $request){
        // https://backend.food2go.pro/admin/category/add
        // Keys
        // category_id, status, priority, active, image, banner_image
        // addons_id[]
        // category_names[{category_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = json_decode($request->category_names[0]);
        foreach ($request->category_names as $item) {
            $item = json_decode($item);
            $this->translate($item['tranlation_name'], $default['category_name'], $item['category_name']); 
        }
        $categoryRequest = $request->only($this->categoryRequest);
        $categoryRequest['name'] = $default['category_name'];
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/category/image');
            $categoryRequest['image'] = $imag_path;
        } // if send image upload it
        if (is_file($request->banner_image)) {
            $imag_path = $this->upload($request, 'banner_image', 'admin/category/banner_image');
            $categoryRequest['banner_image'] = $imag_path;
        } // if send image upload it
        $categories = $this->categories
        ->create($categoryRequest); // create category

        if ($request->addons_id) { 
            $categories->addons()->attach($request->addons_id);
        } // if send addons add it

        return response()->json([
            'success' => 'You add data success'
        ], 200);
    }

    public function modify(CategoryRequest $request, $id){
        // https://backend.food2go.pro/admin/category/update/{id}
        // Keys
        // name, category_id, status, priority, active, image, banner_image
        // addons_id[]
        // category_names[{category_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->category_names[0];
        foreach ($request->category_names as $item) {
            $this->translate($item['tranlation_name'], $default['category_name'], $item['category_name']); 
        }
        $categoryRequest = $request->only($this->categoryRequest);
        $categoryRequest['name'] = $default['category_name'];
        
        $category = $this->categories
        ->where('id', $id)
        ->first(); // get category
        if (is_file($request->image)) {
            $this->deleteImage($category->image);
            $imag_path = $this->upload($request, 'image', 'admin/category/image');
            $categoryRequest['image'] = $imag_path;
        } // if send new image delete old image and add new image
        if (is_file($request->banner_image)) {
            $this->deleteImage($category->banner_image);
            $imag_path = $this->upload($request, 'banner_image', 'admin/category/banner_image');
            $categoryRequest['banner_image'] = $imag_path;
        } // if send new image delete old image and add new image
        $category->update($categoryRequest); // update category

        $category->addons()->sync($request->addons_id);
        // update addons

        return response()->json([
            'success' => 'You update data success'
        ], 200);
    }

    public function delete($id){
        // https://backend.food2go.pro/admin/category/delete/{id}
        $category = $this->categories
        ->where('id', $id)
        ->first(); // get category
        $this->deleteImage($category->image); // delete old image
        $this->deleteImage($category->banner_image); // delete old image
        $category->delete(); // delete category

        return response()->json([
            'success' => 'You delete category success'
        ]);
    }
}
