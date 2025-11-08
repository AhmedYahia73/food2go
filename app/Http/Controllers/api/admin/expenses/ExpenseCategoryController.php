<?php

namespace App\Http\Controllers\api\admin\expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ExpenseCategory;
use App\Models\Translation;
use App\Models\TranslationTbl;

class ExpenseCategoryController extends Controller
{
    public function __construct(private ExpenseCategory $category,
    private Translation $translations, private TranslationTbl $translation_tbl){}

    public function view(Request $request){
        $categories = $this->category
        ->select("id", "name", "status")
        ->get();

        return response()->json([
            "categories" => $categories, 
        ]);
    }

    public function category_item(Request $request, $id){
        $category = $this->category
        ->select("name", "status")
        ->where("id", $id)
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
        }

        return response()->json([
            "category" => $category, 
            "category_names" => $category_names, 
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

        $this->category
        ->where("id", $id)
        ->update([
            "status" => $request->status
        ]);

        return response()->json([
            "success" => "You update status success", 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'names' => ['required', 'array'],
            'names.*.name' => ['required'],
            'names.*.tranlation_name' => ['required'],
            'names.*.tranlation_id' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $names = $request->names;
        $default = $names[0]['name'];
        $categoryRequest = $validator->validated();
        $categoryRequest['name'] = $default;
        $category = $this->category
        ->create($categoryRequest);

        foreach ($names as $item) {
            if (!empty($item['name'])) {
                $category->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default,
                    'value' => $item['name']
                ]); 
            }
        } 

        return response()->json([
            "success" => "You add category success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'names' => ['required', 'array'],
            'names.*.name' => ['required'],
            'names.*.tranlation_name' => ['required'],
            'names.*.tranlation_id' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $names = $request->names;
        $default = $names[0]['name'];
        $categoryRequest = $validator->validated();
        $categoryRequest['name'] = $default;
        $category_item = $this->category
        ->where("id", $id)
        ->first();
        if(!$category_item){
            return response()->json([
                "errors" => "id is wrong"
            ]);
        }
        $category_item->update($categoryRequest);

        $category_item->translations()->delete();
        foreach ($names as $item) {
            if (!empty($item['name'])) {
                $category_item->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default,
                    'value' => $item['name']
                ]); 
            }
        } 

        return response()->json([
            "success" => "You update category success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->category
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete category success"
        ]);
    }
}
