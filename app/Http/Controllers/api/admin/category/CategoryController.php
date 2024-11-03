<?php

namespace App\Http\Controllers\api\admin\category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

use App\Models\Category;
use App\Models\Addon;
use App\Models\Translation;

class CategoryController extends Controller
{
    public function __construct(private Category $categories, private Addon $addons,
    private Translation $translations){}

    public function view(){
        // https://backend.food2go.pro/admin/category
        $categories = $this->categories
        ->with('addons')
        ->orderBy('priority')
        ->get();
        $parent_categories = $this->categories
        ->whereNull('category_id')
        ->get();
        $addons = $this->addons->get();

        return response()->json([
            'categories' => $categories,
            'addons' => $addons,
            'parent_categories' => $parent_categories
        ]);
    }

    public function category($id){
        // https://backend.food2go.pro/admin/category/item/{id}
        $category = $this->categories
        ->where('id', $id)
        ->first();
        $translations = $this->translations
        ->get();
        $category_names = [];
        foreach ($translations as $item) {
            $filePath = base_path("lang/{$item->name}/messages.php");
            if (File::exists($filePath)) {
                $translation_file = require $filePath;
                $category_names[] = [
                    'id' => $item->id,
                    'name' => $translation_file[$category->name] ?? null
                ];
            }
        }

        return response()->json([
            'category' => $category,
            'category_names' => $category_names
        ]);
    }

    public function status(Request $request, $id){
        // https://backend.food2go.pro/admin/category/status/{id}
        $validator = Validator::make($request->all(), [
        'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }

        $this->categories->where('id', $id)
        ->update(['status' => $request->status]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    public function priority(Request $request, $id){
        // https://backend.food2go.pro/admin/category/priority/{id}
        $validator = Validator::make($request->all(), [
        'priority' => 'required|integer',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }

        $this->categories->where('id', $id)
        ->update(['priority' => $request->priority]);

        return response()->json([
            'success' => 'You update priority success'
        ]);
    }
}
