<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\settings\TaxRequest;

use App\Models\Tax;

class TaxController extends Controller
{
    public function __construct(private Tax $tax){}
    protected $taxRequest = [
        'name',
        'type',
        'amount',
    ];

    public function view(){
        $taxes = $this->tax->get();

        return response()->json([
            'taxes' => $taxes
        ]);
    }

    public function create(TaxRequest $request){
        $taxRequest = $request->only($this->taxRequest);
        $this->tax->create($taxRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(TaxRequest $request, $id){
        $taxRequest = $request->only($this->taxRequest);
        $this->tax
        ->where('id', $id)
        ->update($taxRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $this->tax
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
