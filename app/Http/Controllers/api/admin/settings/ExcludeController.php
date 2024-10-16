<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\ExcludeRequest;

use App\Models\Product;
use App\Models\ExcludeProduct;

class ExcludeController extends Controller
{
    public function __construct(private ExcludeProduct $excludes, private Product $products){}
    protected $excludeRequest = [
        'name',
        'product_id',
    ];

    public function view(){
        $excludes = $this->excludes
        ->get();
        $products = $this->products
        ->get();

        return response()->json([
            'excludes' => $excludes,
            'products' => $products,
        ]);
    }

    public function create(ExcludeRequest $request){
        $excludeRequest = $request->only($this->excludeRequest);
        $this->excludes
        ->create($excludeRequest);

        return response()->json([
            'success' => 'You add data success'
        ], 200);
    }

    public function modify(ExcludeRequest $request, $id){
        $excludeRequest = $request->only($this->excludeRequest);
        $this->excludes
        ->where('id', $id)
        ->update($excludeRequest);

        return response()->json([
            'success' => 'You update data success'
        ], 200);
    }

    public function delete($id){     
        $this->excludes
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ], 200);
    }
}
