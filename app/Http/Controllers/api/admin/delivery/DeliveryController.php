<?php

namespace App\Http\Controllers\api\admin\delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\delivery\DeliveryRequest;
use App\Http\Requests\admin\delivery\UpdateDeliveryRequest;
use App\trait\image;

use App\Models\Delivery;

class DeliveryController extends Controller
{
    public function __construct(private Delivery $deliveries){}
    protected $deliveryRequest = [
        'f_name',
        'l_name',
        'identity_type',
        'identity_number',
        'email',
        'phone',
        'password',
        'branch_id',
        'status',
    ];
    use image;

    public function view(){
        $deliveries = $this->deliveries
        ->get();

        return response()->json([
            'deliveries' => $deliveries,
        ]);
    }

    public function create(DeliveryRequest $request)
    {
    }

    public function modify(UpdateDeliveryRequest $request, $id){
    }

    public function delete($id){
    }
}
