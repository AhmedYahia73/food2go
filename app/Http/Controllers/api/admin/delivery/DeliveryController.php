<?php

namespace App\Http\Controllers\api\admin\delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\delivery\DeliveryRequest;
use App\Http\Requests\admin\delivery\UpdateDeliveryRequest;
use App\trait\image;

use App\Models\Delivery;
use App\Models\Branch;

class DeliveryController extends Controller
{
    public function __construct(private Delivery $deliveries, private Branch $branches){}
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
        $branches = $this->branches->get();

        return response()->json([
            'deliveries' => $deliveries,
            'branches' => $branches,
        ]);
    }

    public function create(DeliveryRequest $request){
        // Keys
        // f_name, l_name, identity_type, identity_number, email, phone
        // password, branch_id, status, image, identity_image
        $deliveryRequest = $request->only($this->deliveryRequest);
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/delivery/image');
            $deliveryRequest['image'] = $imag_path;
        }
        if (is_file($request->identity_image)) {
            $imag_path = $this->upload($request, 'identity_image', 'users/delivery/identity_image');
            $deliveryRequest['identity_image'] = $imag_path;
        }
        $this->deliveries->create($deliveryRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(UpdateDeliveryRequest $request, $id){
        // Keys
        // f_name, l_name, identity_type, identity_number, email, phone
        // password, branch_id, status, image, identity_image
        $deliveryRequest = $request->only($this->deliveryRequest);
        $delivery = $this->deliveries
        ->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'users/delivery/image');
            $deliveryRequest['image'] = $imag_path;
            $this->deleteImage($delivery->image);
        }
        if (is_file($request->identity_image)) {
            $imag_path = $this->upload($request, 'identity_image', 'users/delivery/identity_image');
            $deliveryRequest['identity_image'] = $imag_path;
            $this->deleteImage($delivery->identity_image);
        }
        $delivery->update($deliveryRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        $delivery = $this->deliveries
        ->where('id', $id)
        ->first();
        $this->deleteImage($delivery->image);
        $this->deleteImage($delivery->identity_image);
        $delivery->delete();
        
        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
