<?php

namespace App\Http\Controllers\api\admin\point_offers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\point_offers\PointOfferRequest;

use App\Models\Offer;

class PointOffersController extends Controller
{
    public function __construct(private Offer $offers){}
    protected $offerRequest = [
        'product',
        'points',
    ];

    public function view(){
        $offers = $this->offers
        ->get();

        return response()->json([
            'offers' => $offers
        ]);
    }

    public function create(PointOfferRequest $request){

    }

    public function modify(PointOfferRequest $request, $id){

    }

    public function delete($id){

    }
}
