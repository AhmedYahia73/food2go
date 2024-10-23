<?php

namespace App\Http\Controllers\api\customer\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

class ProfileController extends Controller
{
    public function __construct(private User $user){}

    public function update_profile(){
        
    }
}
