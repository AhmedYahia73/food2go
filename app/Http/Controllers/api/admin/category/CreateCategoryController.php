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
        // https://bcknd.food2go.online/admin/category/add
        // Keys
        // category_id, status, priority, active, image, banner_image
        // addons_id[]
        // category_names[{category_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $request->addons_id = is_string($request->addons_id) ? json_decode($request->addons_id)
        : $request->addons_id;
        $request->category_names = is_string($request->category_names) ? json_decode($request->category_names)
        : $request->category_names;
        $default = $request->category_names[0];
        $categoryRequest = $request->only($this->categoryRequest);
        $categoryRequest['name'] = $default['category_name'];
        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/category/image');
            $categoryRequest['image'] = $imag_path;
        } // if send image upload it
        if ($request->banner_image) {
            $imag_path = $this->upload($request, 'banner_image', 'admin/category/banner_image');
            $categoryRequest['banner_image'] = $imag_path;
        } // if send image upload it 
        if (empty($request->category_id)) {
            $category = $this->categories
            ->where('priority', $request->priority)
            ->whereNull('category_id')
            ->first();
            if (!empty($category)) {
                $this->categories
                ->where('priority', '>=', $request->priority)
                ->whereNull('category_id')
                ->increment('priority');
            }
        }
        else {
            $category = $this->categories
            ->where('priority', $request->priority)
            ->whereNotNull('category_id')
            ->first();
            if (!empty($category)) {
                $this->categories
                ->where('priority', '>=', $request->priority)
                ->whereNotNull('category_id')
                ->increment('priority');
            }
        }
        $categories = $this->categories
        ->create($categoryRequest); // create category
        foreach ($request->category_names as $item) {
            if (!empty($item['category_name'])) {
                $categories->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['category_name'],
                    'value' => $item['category_name']
                ]);
            }
        }
        if ($request->addons_id) { 
            $categories->addons()->attach($request->addons_id);
        } // if send addons add it

        return response()->json([
            'success' => 'You add data success'
        ], 200);
    }

    public function modify(CategoryRequest $request, $id){
        // https://bcknd.food2go.online/admin/category/update/{id}
        // Keys
        // name, category_id, status, priority, active, image, banner_image
        // addons_id[]
        // category_names[{category_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->category_names[0];
        $categoryRequest = $request->only($this->categoryRequest);
        $categoryRequest['name'] = $default['category_name'];
        
        $category = $this->categories
        ->where('id', $id)
        ->first(); // get category
        if (empty($request->category_id)) {
            $category_item = $this->categories
            ->where('priority', $request->priority)
            ->whereNull('category_id')
            ->first();
            if (!empty($category_item)) {
                $this->categories
                ->where('priority', '>=', $request->priority)
                ->whereNull('category_id')
                ->increment('priority');
            }
        }
        else {
            $category_item = $this->categories
            ->where('priority', $request->priority)
            ->whereNotNull('category_id')
            ->first();
            if (!empty($category_item)) {
                $this->categories
                ->where('priority', '>=', $request->priority)
                ->whereNotNull('category_id')
                ->increment('priority');
            }
        }
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
        $category->translations()->delete();
        foreach ($request->category_names as $item) {
            if (!empty($item['category_name'])) {
                $category->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['category_name'],
                    'value' => $item['category_name']
                ]); 
            }
        }

        $category->addons()->sync($request->addons_id);
        // update addons

        return response()->json([
            'success' => 'You update data success'
        ], 200);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/category/delete/{id}
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
