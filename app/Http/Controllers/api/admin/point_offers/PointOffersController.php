<?php

namespace App\Http\Controllers\api\admin\point_offers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\point_offers\PointOfferRequest;
use App\trait\image;

use App\Models\Offer;

class PointOffersController extends Controller
{
    public function __construct(private Offer $offers){}
    protected $offerRequest = [
        'product',
        'points',
    ];
    use image;

    public function view(){
        $offers = $this->offers
        ->get();

        return response()->json([
            'offers' => $offers
        ]);
    }

    public function create(PointOfferRequest $request){
        $offerRequest = $request->only($this->offerRequest);
        if (is_file($request->image)) {
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
        $offerRequest = $request->only($this->offerRequest);
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
