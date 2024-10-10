<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;

class OrderController extends Controller
{
    public function __construct(private Category $categories){}

    public function categories(){
        $categories = $this->categories
        ->with(['sub_categories.products.addons'])
        ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }
}
