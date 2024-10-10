<?php

namespace App\Http\Controllers\api\admin\category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

    public function create(){
        $categories = $this->categories
        ->create();
    }
}
