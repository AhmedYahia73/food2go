<?php

namespace App\Http\Controllers\api\admin\addon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\addon\AddonRequest;
use App\trait\translaion;
use Illuminate\Support\Facades\File;

use App\Models\Addon;
use App\Models\Tax;
use App\Models\Translation;

class AddonController extends Controller
{
    public function __construct(private Addon $addons, private Tax $taxes,
    private Translation $translations){}
    protected $addonRequest = [
        'price',
        'tax_id',
        'quantity_add',
    ];
    use translaion;

    public function view(){
        // https://bcknd.food2go.online/admin/addons
        $addons = $this->addons
        ->with('tax')
        ->get();
        $taxes = $this->taxes
        ->get();

        return response()->json([
            'addons' => $addons,
            'taxes' => $taxes,
        ], 200);
    }

    public function addon($id){
        // https://bcknd.food2go.online/admin/addons/item/{id}
        $addon = $this->addons
        ->with('tax')
        ->where('id', $id)
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $addons_names = [];
        foreach ($translations as $item) {
            $createNewPlan->translations()->create($translation);
            $filePath = base_path("lang/{$item->name}/messages.php");
            if (File::exists($filePath)) {
                $translation_file = require $filePath;
                $addons_names[] = [
                    'id' => $item->id,
                    'lang' => $item->name,
                    'name' => $translation_file[$addon->name] ?? null,
                ];
            }
        }

        return response()->json([
            'addon' => $addon,
            'addons_names' => $addons_names
        ]);
    }

    public function create(AddonRequest $request){
        // https://bcknd.food2go.online/admin/addons/add
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
        // https://bcknd.food2go.online/admin/addons/update/{id}
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
        // https://bcknd.food2go.online/admin/addons/delete/{id}
        $this->addons
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success',
        ], 200);
    }
}
