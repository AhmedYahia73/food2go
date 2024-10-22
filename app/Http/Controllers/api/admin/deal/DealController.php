<?php

namespace App\Http\Controllers\api\admin\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\deal\DealRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Deal;
use App\Models\DealTimes;

class DealController extends Controller
{
    public function __construct(private Deal $deals, private DealTimes $deal_times){}
    protected $dealRequest = [
        'title',
        'description',
        'price',
        'status',
    ];
    use image;

    public function view(){
        $deals = $this->deals
        ->with('times')
        ->get();

        return response()->json([
            'deals' => $deals
        ]);
    }

    public function status(Request $request ,$id){
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
        $this->deals->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        if ($request->status == 0) {
            return response()->json([
                'success' => 'banned'
            ]);
        } else {
            return response()->json([
                'success' => 'active'
            ]);
        }
    }

    public function create(DealRequest $request){
        // Keys
        // title, description, price, status, image
        // times[0][day], times[0][from], times[0][to]
        $dealRequest = $request->only($this->dealRequest);
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/deals/image');
            $dealRequest['image'] = $imag_path;
        }
        $deal = $this->deals
        ->create($dealRequest);
        if ($request->times) {
            foreach ($request->times as $item) {
                $this->deal_times->create([
                    'deal_id' => $deal->id,
                    'day' => $item['day'],
                    'from' => $item['from'],
                    'to' => $item['to'],
                ]);
            }
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(DealRequest $request, $id){
        // Keys
        // title, description, price, status, image
        // times[0][day], times[0][from], times[0][to]
        $deal = $this->deals
        ->where('id', $id)
        ->first();
        $dealRequest = $request->only($this->dealRequest);
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/deals/image');
            $dealRequest['image'] = $imag_path;
            $this->deleteImage($deal->image);
        }
        $deal->update($dealRequest);
        $deal->times()->delete();
        if ($request->times) {
            foreach ($request->times as $item) {
                $deal->times()->create([
                    'day' => $item['day'],
                    'from' => $item['from'],
                    'to' => $item['to'],
                ]);
            }
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $deal = $this->deals
        ->where('id', $id)
        ->first();
        $this->deleteImage($deal->image);
        $deal->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
