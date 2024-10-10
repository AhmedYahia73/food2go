<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\ExtraRequest;

use App\Models\Product;
use App\Models\VariationProduct;
use App\Models\ExtraProduct;

class ExtraController extends Controller
{
    public function __construct(private Product $products, private VariationProduct $variations,
    private ExtraProduct $extra){}
    protected $extraRequest = [
        'name',
        'price',
        'product_id',
        'variation_id',
    ];

    public function view(){
        $products = $this->products
        ->get();
        $variations = $this->variations
        ->get();
        $extra = $this->extra
        ->get();

        return response()->json([
            'products' => $products,
            'variations' => $variations,
            'extra' => $extra
        ]);
    }

    public function create(ExtraRequest $request){
        $extraRequest = $request->only($this->extraRequest);
        $this->extra->create($extraRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(ExtraRequest $request, $id){
        $extraRequest = $request->only($this->extraRequest);
        $extra = $this->extra
        ->where('id', $id)
        ->first();
        $extra->update($extraRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $extra = $this->extra
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
