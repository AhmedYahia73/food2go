<?php

namespace App\Http\Controllers\api\admin\addon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\addon\AddonRequest;
use App\trait\translaion;

use App\Models\Addon;
use App\Models\Tax;

class AddonController extends Controller
{
    public function __construct(private Addon $addons, private Tax $taxes){}
    protected $addonRequest = [
        'price',
        'tax_id',
        'quantity_add',
    ];
    use translaion;

    public function view(){
        // https://backend.food2go.pro/admin/addons
        $addons = $this->addons
        ->get();
        $taxes = $this->taxes
        ->get();

        return response()->json([
            'addons' => $addons,
            'taxes' => $taxes,
        ], 200);
    }

    public function create(AddonRequest $request){
        // https://backend.food2go.pro/admin/addons/add
        // Keys
        // price, tax_id, quantity_add
        // addon_names[{addon_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->addon_names[0];
        foreach ($request->addon_names as $item) {
            $this->translate($item['tranlation_name'], $default['addon_name'], $item['addon_name']); 
        }
        $addonRequest = $request->only($this->addonRequest);
        $addonRequest['name'] = $default['addon_name'];
        
        $this->addons
        ->create($addonRequest);

        return response()->json([
            'success' => 'You add data success'
        ], 200);
    }

    public function modify(AddonRequest $request, $id){
        // https://backend.food2go.pro/admin/addons/update/{id}
        // Keys
        // price, tax_id, quantity_add
        // addon_names[{addon_name, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->addon_names[0];
        foreach ($request->addon_names as $item) {
            $this->translate($item['tranlation_name'], $default['addon_name'], $item['addon_name']); 
        }
        $addonRequest = $request->only($this->addonRequest);
        $addonRequest['name'] = $default['addon_name'];
        
        $this->addons
        ->where('id', $id)
        ->update($addonRequest);

        return response()->json([
            'success' => 'You update data success'
        ], 200);
    }

    public function delete($id){
        // https://backend.food2go.pro/admin/addons/delete/{id}
        $this->addons
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success',
        ], 200);
    }
}
