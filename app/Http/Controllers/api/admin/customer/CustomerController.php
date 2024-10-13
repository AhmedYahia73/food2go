<?php

namespace App\Http\Controllers\api\admin\customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\CustomerRequest;
use App\Http\trait\image;

use App\Models\User;

class CustomerController extends Controller
{
    public function __construct(private User $customers){}
    protected $userRequest = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'password',
    ];
    use image;

    public function view(){
        $customers = $this->customers
        ->get();

        return response()->json([
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        $data = $request->only($this->userRequest);
        $user = $this->user->create($data);
    }

    public function modify(){
        
    }

    public function delete(){
        
    }
}
