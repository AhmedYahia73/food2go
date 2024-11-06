<?php

namespace App\Http\Controllers\api\admin\point_offers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\point_offers\PointOfferRequest;
use App\trait\image;
use App\trait\translaion;

use App\Models\Offer;

class PointOffersController extends Controller
{
    public function __construct(private Offer $offers){}
    protected $offerRequest = [
        'product',
        'points',
    ];
    use image;
    use translaion;

    public function view(){
        // https://backend.food2go.pro/admin/offer
        $offers = $this->offers
        ->get();

        return response()->json([
            'offers' => $offers
        ]);
    }

    public function create(PointOfferRequest $request){
        // https://backend.food2go.pro/admin/offer/add
        // Keys
        // points, image
        // offer_names[{offer_product, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->offer_names[0];
        foreach ($request->offer_names as $item) {
            $this->translate($item['tranlation_name'], $default['offer_product'], $item['offer_product']); 
        }
        $offerRequest = $request->only($this->offerRequest);
        $offerRequest['product'] = $default['offer_product'];

        if ($request->image) {
            $imag_path = $this->upload($request, 'image', 'admin/point_offers/image');
            $offerRequest['image'] = $imag_path;
        }
        
        $offer = $this->offers
        ->create($offerRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(PointOfferRequest $request, $id){
        // https://backend.food2go.pro/admin/offer/update/{id}
        // Keys
        // points, image
        // offer_names[{offer_product, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->offer_names[0];
        foreach ($request->offer_names as $item) {
            $this->translate($item['tranlation_name'], $default['offer_product'], $item['offer_product']); 
        }
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

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://backend.food2go.pro/admin/offer/delete/{id}
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
