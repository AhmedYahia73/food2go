<?php

namespace App\Http\Controllers\api\admin\cafe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\cafe\CafeTableRequest;
use App\trait\image;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

use App\Models\CafeLocation;
use App\Models\CafeTable;
use App\Models\Branch;

class CafeTablesController extends Controller
{
    public function __construct(private CafeLocation $locations,
    private CafeTable $cafe_tables, private Branch $branchs){}
    use image;

    public function view(){
        // /admin/caffe_tables
        $locations = $this->locations
        ->get();
        $branchs = $this->branchs
        ->where('status', 1)
        ->get();
        $cafe_tables = $this->cafe_tables
        ->with('branch', 'location')
        ->get();

        return response()->json([
            'locations' => $locations,
            'branchs' => $branchs,
            'cafe_tables' => $cafe_tables,
        ]);
    }

    public function table($id){
        // /admin/caffe_tables/item/{id}
        $cafe_tables = $this->cafe_tables
        ->with('branch', 'location')
        ->where('id', $id)
        ->get();

        return response()->json([ 
            'cafe_tables' => $cafe_tables,
        ]);
    }

    public function status(Request $request, $id){
        // /admin/caffe_tables/status/{id}
        // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }
        $cafe_tables = $this->cafe_tables
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function occupied(Request $request, $id){
        // /admin/caffe_tables/occupied/{id}
        // Keys
        // occupied
        $validator = Validator::make($request->all(), [
            'occupied' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'error' => $validator->errors(),
            ],400);
        }
        $cafe_tables = $this->cafe_tables
        ->where('id', $id)
        ->update([
            'occupied' => $request->occupied
        ]);

        return response()->json([
            'success' => $request->occupied ? 'active' : 'banned'
        ]);
    }

    public function create(CafeTableRequest $request){
        // /admin/caffe_tables/add
        // Keys
        // qr_code, table_number, location_id, branch_id, capacity
        // occupied, status

        $tablesRequest = $request->validated(); 

        $cafe_tables = $this->cafe_tables
        ->create($tablesRequest); 
     
        $qrContent = 'cafe table ' . $cafe_tables->id;
    
        $qrImage = QrCode::format('png')->size(300)->generate($qrContent);
        $fileName = 'admin/cafe/tables/qr/'.$cafe_tables->id.'.png';
    
        Storage::disk('public')->put($fileName, $qrImage);
    
        $cafe_tables->qr_code = $fileName;
        $cafe_tables->save();
     
        return response()->json([
            'success' => $cafe_tables
        ]);
    }

    public function modify(CafeTableRequest $request, $id){
        // /admin/caffe_tables/update/{id}
        // Keys
        // qr_code, table_number, location_id, branch_id, capacity
        // occupied, status
        $tablesRequest = $request->validated();
        $cafe_tables = $this->cafe_tables
        ->where('id', $id)
        ->first();
        if (empty($cafe_tables)) {
            return response()->json([
                'errors' => 'id not found'
            ], 400);
        }
        if ($request->qr_code && !is_string($request->qr_code)) {
            $imag_path = $this->upload($request, 'qr_code', 'admin/cafe/tables/qr_code');
            $tablesRequest['qr_code'] = $imag_path; 
            $this->deleteImage($cafe_tables->qr_code);
        } // if send image upload it

        $cafe_tables->update($tablesRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // /admin/caffe_tables/delete/{id}
        $cafe_tables = $this->cafe_tables
        ->where('id', $id)
        ->first();
        if (empty($cafe_tables)) {
            return response()->json([
                'errors' => 'id not found'
            ], 400);
        }
        $this->deleteImage($cafe_tables->qr_code);

        $cafe_tables->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
