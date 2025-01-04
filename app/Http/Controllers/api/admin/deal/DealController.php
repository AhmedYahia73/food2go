<?php

namespace App\Http\Controllers\api\admin\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\deal\DealRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\trait\image;
use App\trait\translaion;

use App\Models\Deal;
use App\Models\DealTimes;
use App\Models\Translation;
use App\Models\TranslationTbl;

class DealController extends Controller
{
    public function __construct(private Deal $deals, private DealTimes $deal_times,
    private Translation $translations, private TranslationTbl $translation_tbl){}
    protected $dealRequest = [
        'title',
        'description',
        'price',
        'status',
        'daily',
        'start_date',
        'end_date',
    ];
    use image;
    use translaion;

    public function view(){
        // https://bcknd.food2go.online/admin/deal
        $deals = $this->deals
        ->with('times')
        ->get();

        return response()->json([
            'deals' => $deals
        ]);
    }

    public function deal($id){
        // https://bcknd.food2go.online/admin/deal/item/{id}
        $deal = $this->deals
        ->with('times')
        ->where('id', $id)
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $deal_names = [];
        $deal_descriptions = [];
        foreach ($translations as $item) {
            $deal_name = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $deal->title)
            ->first();
            $deal_description = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $deal->description)
            ->first();
           $deal_names[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'deal_title' => $deal_name->value ?? null,
           ];
           $deal_descriptions[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'deal_description' => $deal_description->value ?? null,
           ];
        }
        $deal->deal_names = $deal_names;
        $deal->deal_descriptions = $deal_descriptions;

        return response()->json([
            'deal' => $deal
        ]);
    }

    public function status(Request $request ,$id){
        // https://bcknd.food2go.online/admin/deal/status/{id}
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
        // https://bcknd.food2go.online/admin/deal/add
        // Keys
        // price, status, image
        // times[0][day], times[0][from], times[0][to]
        // daily, start_date, end_date
        // Days [Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday]
        // deal_names[{deal_title, tranlation_id, tranlation_name}]
        // deal_descriptions[{deal_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->deal_names[0];
        $default_description = $request->deal_descriptions[0];
        $dealRequest = $request->only($this->dealRequest);
        $dealRequest['title'] = $default['deal_title'];
        $dealRequest['description'] = $default_description['deal_description'];

        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/deals/image');
            $dealRequest['image'] = $imag_path;
        }
        $deal = $this->deals
        ->create($dealRequest);
        foreach ($request->deal_names as $item) {
            if (!empty($item['deal_title'])) {
                $deal->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['deal_title'],
                    'value' => $item['deal_title']
                ]); 
            }
        }
        foreach ($request->deal_descriptions as $item) {
            if (!empty($item['deal_description'])) {
                $deal->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default_description['deal_description'],
                    'value' => $item['deal_description']
                ]);
            }
        }
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
        // https://bcknd.food2go.online/admin/deal/update/{id}
        // Keys
        // price, status, image
        // times[0][day], times[0][from], times[0][to]
        // daily, start_date, end_date
        // Days [Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday]
        // deal_names[{deal_title, tranlation_id, tranlation_name}]
        // deal_descriptions[{deal_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->deal_names[0];
        $default_description = $request->deal_descriptions[0];
        $dealRequest = $request->only($this->dealRequest);
        $dealRequest['title'] = $default['deal_title'];
        $dealRequest['description'] = $default_description['deal_description'];
        $deal = $this->deals
        ->where('id', $id)
        ->first();
        $deal->translations()->delete();
        foreach ($request->deal_names as $item) {
            if (!empty($item['deal_title'])) {
                $deal->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['deal_title'],
                    'value' => $item['deal_title']
                ]);
            }
        }
        foreach ($request->deal_descriptions as $item) {
            if (!empty($item['deal_description'])) {
                $deal->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default_description['deal_description'],
                    'value' => $item['deal_description']
                ]);  
            }
        }
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
        // https://bcknd.food2go.online/admin/deal/delete/{id}
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
