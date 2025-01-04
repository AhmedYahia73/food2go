<?php

namespace App\Http\Controllers\api\admin\point_offers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\point_offers\PointOfferRequest;
use Illuminate\Support\Facades\File;
use App\trait\image;
use App\trait\translaion;

use App\Models\TranslationTbl;
use App\Models\Offer;
use App\Models\Translation;

class PointOffersController extends Controller
{
    public function __construct(private Offer $offers, private Translation $translations, 
    private TranslationTbl $translation_tbl){}
    protected $offerRequest = [
        'product',
        'points',
    ];
    use image;
    use translaion;

    public function view(){
        // https://bcknd.food2go.online/admin/offer
        $offers = $this->offers
        ->get();

        return response()->json([
            'offers' => $offers
        ]);
    }


    public function offer($id){
        // https://bcknd.food2go.online/admin/offer/item/{id}
        $offer = $this->offers
        ->where('id', $id)
        ->first();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $offer_names = [];
        foreach ($translations as $item) {
            $offer_name = $this->translation_tbl
            ->where('locale', $item->name)
            ->where('key', $offer->product)
            ->first();
           $offer_names[] = [
               'tranlation_id' => $item->id,
               'tranlation_name' => $item->name,
               'offer_product' => $offer_name->value ?? null,
           ];

            // $filePath = base_path("lang/{$item->name}/messages.php");
            // if (File::exists($filePath)) {
            //     $translation_file = require $filePath;
            //     $offer_names[] = [
            //         'tranlation_id' => $item->id,
            //         'tranlation_name' => $item->name,
            //         'offer_product' => $translation_file[$offer->title] ?? null
            //     ];
            // }
        }
        $offer->offer_names = $offer_names;

        return response()->json([
            'offer' => $offer
        ]);
    }

    public function create(PointOfferRequest $request){
        // https://bcknd.food2go.online/admin/offer/add
        // Keys
        // points, image
        // offer_names[{offer_product, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $request->offer_names = is_string($request->offer_names) ? json_decode($request->offer_names): 
        $request->offer_names;
        $default = $request->offer_names[0];
        $offerRequest = $request->only($this->offerRequest);
        $offerRequest['product'] = $default['offer_product'];

        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/point_offers/image');
            $offerRequest['image'] = $imag_path;
        } 
        $offer = $this->offers
        ->create($offerRequest);
        foreach ($request->offer_names as $item) {
            if (!empty($item['offer_product'])) {
                $offer->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['offer_product'],
                    'value' => $item['offer_product']
                ]); 
            }
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(PointOfferRequest $request, $id){
        // https://bcknd.food2go.online/admin/offer/update/{id}
        // Keys
        // points, image
        // offer_names[{offer_product, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->offer_names[0]; 
        $offerRequest = $request->only($this->offerRequest);
        $offerRequest['product'] = $default['offer_product'];

        $offer = $this->offers
        ->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/point_offers/image');
            $offerRequest['image'] = $imag_path;
            $this->deleteImage($offer->image);
        }
        $offer->update($offerRequest);
        $offer->translations()->delete();
        foreach ($request->offer_names as $item) {
            if (!empty($item['offer_product'])) {
                $offer->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['offer_product'],
                    'value' => $item['offer_product']
                ]); 
            }
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/offer/delete/{id}
        $offer = $this->offers
        ->where('id', $id)
        ->first();
        $this->deleteImage($offer->image);
        $offer->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
