<?php

namespace App\Http\Controllers\api\admin\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\deal\DealRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;
use App\trait\translaion;

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
    use translaion;

    public function view(){
        // https://backend.food2go.pro/admin/deal
        $deals = $this->deals
        ->with('times')
        ->get();

        return response()->json([
            'deals' => $deals
        ]);
    }

    public function status(Request $request ,$id){
        // https://backend.food2go.pro/admin/deal/status/{id}
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
        // https://backend.food2go.pro/admin/deal/add
        // Keys
        // price, status, image
        // times[0][day], times[0][from], times[0][to]
        // Days [Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday]
        // deal_names[{deal_title, deal_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->deal_names[0];
        foreach ($request->deal_names as $item) {
            $this->translate($item['tranlation_name'], $default['deal_title'], $item['deal_title']); 
            $this->translate($item['tranlation_name'], $default['deal_description'], $item['deal_description']); 
        }
        $dealRequest = $request->only($this->dealRequest);
        $dealRequest['title'] = $default['deal_title'];
        $dealRequest['description'] = $default['deal_description'];

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
        // https://backend.food2go.pro/admin/deal/update/{id}
        // Keys
        // title, description, price, status, image
        // times[0][day], times[0][from], times[0][to]
        // Days [Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday]
        // deal_names[{deal_title, deal_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->deal_names[0];
        foreach ($request->deal_names as $item) {
            $this->translate($item['tranlation_name'], $default['deal_title'], $item['deal_title']); 
            $this->translate($item['tranlation_name'], $default['deal_description'], $item['deal_description']); 
        }
        $dealRequest = $request->only($this->dealRequest);
        $dealRequest['title'] = $default['deal_title'];
        $dealRequest['description'] = $default['deal_description'];
        $deal = $this->deals
        ->where('id', $id)
        ->first();
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
        // https://backend.food2go.pro/admin/deal/delete/{id}
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
