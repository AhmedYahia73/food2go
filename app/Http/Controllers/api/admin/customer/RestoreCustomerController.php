<?php

namespace App\Http\Controllers\api\admin\customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

class RestoreCustomerController extends Controller
{
    public function __construct(private User $users){}

    public function view(Request $request){
        $users = $this->users
        ->where("deleted_at", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "email" => $item->email,
                "phone" => $item->phone,
                "image_link" => $item->image_link,
            ];
        });

        return response()->json([
            "users" => $users
        ]);
    }

    public function restore(Request $request, $id){
        $users = $this->users
        ->where("id", $id)
        ->update([
            "deleted_at" => 0
        ]);

        return response()->json([
            "success" => 'You restore user success'
        ]);
    }
}
