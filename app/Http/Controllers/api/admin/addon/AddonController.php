<?php

namespace App\Http\Controllers\api\admin\addon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\addon\AddonRequest;

use App\Models\Addon;
use App\Models\Tax;

class AddonController extends Controller
{
    public function __construct(private Addon $addons, private Tax $taxes){}
    protected $addonRequest = [
        'name',
        'price',
        'tax_id',
    ];

    public function view(){
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
        $addonRequest = $request->only($this->addonRequest);
        $this->addons
        ->create($addonRequest);

        return response()->json([
            'success' => 'You add data success'
        ], 200);
    }

    public function modify(AddonRequest $request, $id){
        $addonRequest = $request->only($this->addonRequest);
        $this->addons
        ->where('id', $id)
        ->update($addonRequest);

        return response()->json([
            'success' => 'You update data success'
        ], 200);
    }

    public function delete($id){
        $this->addons
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success',
        ], 200);
    }
}
