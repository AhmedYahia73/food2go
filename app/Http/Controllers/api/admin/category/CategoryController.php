<?php

namespace App\Http\Controllers\api\admin\category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;
use App\Models\Addon;

class CategoryController extends Controller
{
    public function __construct(private Category $categories, private Addon $addons){}

    public function view(){
        $categories = $this->categories
        ->with('addons')
        ->orderBy('priority')
        ->get();
        $addons = $this->addons->get();

        return response()->json([
            'categories' => $categories,
            'addons' => $addons,
        ]);
    }

    public function status(Request $request, $id){
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
