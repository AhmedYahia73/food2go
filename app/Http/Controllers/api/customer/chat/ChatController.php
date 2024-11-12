<?php

namespace App\Http\Controllers\api\customer\chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Chat;

class ChatController extends Controller
{
    public function __construct(private Chat $chat){}

    
}
