<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\ExcludeRequest;
use App\trait\translaion;

use App\Models\Product;
use App\Models\ExcludeProduct;

class ExcludeController extends Controller
{
    public function __construct(private ExcludeProduct $excludes, private Product $products){}
    protected $excludeRequest = [
        'name',
        'product_id',
    ];
    use translaion;

    public function view(){
        // https://bcknd.food2go.online/admin/settings/exclude
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
        // https://bcknd.food2go.online/admin/settings/exclude/add
        // Keys
        // name, product_id
        // exclude_names[{exclude_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->exclude_names[0];
        foreach ($request->exclude_names as $item) {
            $this->translate($item['tranlation_name'], $default['exclude_name'], $item['exclude_name']); 
        }
        $excludeRequest = $request->only($this->excludeRequest);
        $excludeRequest['name'] = $default['exclude_name'];
        $this->excludes
        ->create($excludeRequest);

        return response()->json([
            'success' => 'You add data success'
        ], 200);
    }

    public function modify(ExcludeRequest $request, $id){
        // https://bcknd.food2go.online/admin/settings/exclude/update/{id}
        // Keys
        // name, product_id
        // exclude_names[{exclude_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->exclude_names[0];
        foreach ($request->exclude_names as $item) {
            $this->translate($item['tranlation_name'], $default['exclude_name'], $item['exclude_name']); 
        }
        $excludeRequest = $request->only($this->excludeRequest);
        $excludeRequest['name'] = $default['exclude_name'];
        $this->excludes
        ->where('id', $id)
        ->update($excludeRequest);

        return response()->json([
            'success' => 'You update data success'
        ], 200);
    }

    public function delete($id){ 
        // https://bcknd.food2go.online/admin/settings/exclude/delete/{id}
        $this->excludes
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ], 200);
    }
}
