<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\ExtraRequest;
use App\trait\translaion;

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
    use translaion;

    public function view(){
        // https://bcknd.food2go.online/admin/settings/extra
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
        // https://bcknd.food2go.online/admin/settings/extra/add
        // Keys
        // name, price, product_id, variation_id
        // extra_names[{extra_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->extra_names[0];
        foreach ($request->extra_names as $item) {
            $this->translate($item['tranlation_name'], $default['extra_name'], $item['extra_name']); 
        }
        $extraRequest = $request->only($this->extraRequest);
        $extraRequest['name'] = $default['extra_name'];
        $this->extra->create($extraRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(ExtraRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/extra/update/{id}
        // Keys
        // name, price, product_id, variation_id
        // extra_names[{extra_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->extra_names[0];
        foreach ($request->extra_names as $item) {
            $this->translate($item['tranlation_name'], $default['extra_name'], $item['extra_name']); 
        }
        $extraRequest = $request->only($this->extraRequest);
        $extraRequest['name'] = $default['extra_name'];
        $extra = $this->extra
        ->where('id', $id)
        ->first();
        $extra->update($extraRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/settings/extra/delete/{id}
        $extra = $this->extra
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
