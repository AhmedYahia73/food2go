<?php

namespace App\Http\Controllers\api\admin\category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\category\CategoryRequest;
use App\trait\image;

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
    ];
    use image;

    public function create(CategoryRequest $request){
        $categoryRequest = $request->only($this->categoryRequest);
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
        $categoryRequest = $request->only($this->categoryRequest);
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
