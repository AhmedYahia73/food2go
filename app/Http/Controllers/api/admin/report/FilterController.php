<?php

namespace App\Http\Controllers\api\admin\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\FilterSaved;

class FilterController extends Controller
{

    public function __construct(private FilterSaved $filter_saved){}

    public function lists(Request $request){
        $filter_types = [
            "financial_report",
            "order_report",
        ];
        return response()->json([
            'filter_types' => $filter_types
        ]);
    }

    public function view(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:financial_report,order_report'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $filters = $this->filter_saved
        ->select('id', 'name', 'filter_obj')
        ->where('type', $request->type)
        ->get();

        return response()->json([
            'filters' => $filters,
        ]);
    }

    public function filter_item(Request $request, $id){
        $filter = $this->filter_saved
        ->select('id', 'name', 'filter_obj')
        ->where('id', $id)
        ->first();

        return response()->json([
            'filter' => $filter,
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'filter_obj' => ['required', 'array'],
            'type' => ['required', 'in:financial_report,order_report'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $this->filter_saved
        ->create([
            'name' => $request->name,
            'filter_obj' => json_encode($request->filter_obj),
            'type' => $request->type,
        ]);

        return response()->json([
            'success' => "You add filter success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'filter_obj' => ['required', 'array'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $this->filter_saved
        ->where('id', $id)
        ->update([
            'name' => $request->name,
            'filter_obj' => json_encode($request->filter_obj),
        ]);

        return response()->json([
            'success' => "You update filter success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->filter_saved
        ->where("id", $id)
        ->delete();

        return response()->json([
            'success' => "You delete filter success"
        ]);
    }
}
