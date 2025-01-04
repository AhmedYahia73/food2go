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
use App\Models\TranslationTbl;

class AddonController extends Controller
{
    public function __construct(private Addon $addons, private Tax $taxes,
    private Translation $translations, private TranslationTbl $translation_tbl){}
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
             $addon_name = $this->translation_tbl
             ->where('locale', $item->name)
             ->where('key', $addon->name)
             ->first();
            $addons_names[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'addon_name' => $addon_name->value ?? null,
            ];
            // $filePath = base_path("lang/{$item->name}/messages.php");
            // if (File::exists($filePath)) {
            //     $translation_file = require $filePath;
            //     $addons_names[] = [
            //         'id' => $item->id,
            //         'lang' => $item->name,
            //         'name' => $translation_file[$addon->name] ?? null,
            //     ];
            // }
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
        $addonRequest = $request->only($this->addonRequest);
        $addonRequest['name'] = $default['addon_name'];
        $addon = $this->addons
        ->create($addonRequest);

        foreach ($request->addon_names as $item) {
            if (!empty($item['addon_name'])) {
                $addon->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['addon_name'],
                    'value' => $item['addon_name']
                ]); 
            }
        }

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
        $addonRequest = $request->only($this->addonRequest);
        $addonRequest['name'] = $default['addon_name'];
        
        $addon = $this->addons
        ->where('id', $id)
        ->first();
        $addon->update($addonRequest);
        $addon->translations()->delete(); 
        foreach ($request->addon_names as $item) {
            if (!empty($item['addon_name'])) {
                $addon->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['addon_name'],
                    'value' => $item['addon_name']
                ]); 
            } 
        }

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
