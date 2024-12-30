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
use App\Models\TranslationTbl;

class CategoryController extends Controller
{
    public function __construct(private Category $categories, private Addon $addons,
    private Translation $translations, private TranslationTbl $translation_tbl){}

    public function view(){
        // https://bcknd.food2go.online/admin/category
        $categories = $this->categories
        ->with('addons')
        ->orderBy('priority')
        ->get();
        $parent_categories = $this->categories
        ->with('sub_categories')
        ->whereNull('category_id')
        ->orderBy('priority')
        ->get();
        $sub_categories = $this->categories
        ->whereNotNull('category_id')
        ->orderBy('priority')
        ->get();
        $addons = $this->addons->get();
        $counter = [];
        for ($i=1; $i <= count($categories) + 1; $i++) { 
            $counter[] = $i;
        }

        return response()->json([
            'categories' => $categories,
            'addons' => $addons,
            'parent_categories' => $parent_categories,
            'sub_categories' => $sub_categories,
            'counter' => $counter
        ]);
    }

    public function category($id){
        // https://bcknd.food2go.online/admin/category/item/{id}
        $category = $this->categories
        ->with('addons')
        ->with('parent')
        ->where('id', $id)
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $category_names = [];
        foreach ($translations as $item) {
            $category_name = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $category->name)
            ->first();
           $category_names[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'category_name' => $category_name->value ?? null,
           ];
            // $filePath = base_path("lang/{$item->name}/messages.php");
            // if (File::exists($filePath)) {
            //     $translation_file = require $filePath;
            //     $category_names[] = [
            //         'id' => $item->id,
            //         'lang' => $item->name,
            //         'name' => $translation_file[$category->name] ?? null
            //     ];
            // }
        }

        return response()->json([
            'category' => $category,
            'category_names' => $category_names
        ]);
    }

    public function status(Request $request, $id){
        // https://bcknd.food2go.online/admin/category/status/{id}
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

    public function active(Request $request, $id){
        // https://bcknd.food2go.online/admin/category/active/{id}
        // key
        // active
        $validator = Validator::make($request->all(), [
        'active' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }

        $this->categories->where('id', $id)
        ->update(['active' => $request->active]);

        return response()->json([
            'success' => 'You update active success'
        ]);
    }

    public function priority(Request $request, $id){
        // https://bcknd.food2go.online/admin/category/priority/{id}
        $validator = Validator::make($request->all(), [
        'priority' => 'required|integer',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }
        $current_category = $this->categories
        ->where('id', $id)
        ->first();
        if (empty($current_category->category_id)) {
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
        $current_category->update(['priority' => $request->priority]);

        return response()->json([
            'success' => 'You update priority success'
        ]);
    }
}
