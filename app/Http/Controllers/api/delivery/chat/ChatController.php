<?php

namespace App\Http\Controllers\api\delivery\chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Events\ChatEvent;

use App\Models\Chat;

class ChatController extends Controller
{
    public function __construct(private Chat $chat){}
    protected $chatRequest = [
        'order_id',
        'user_id',
        'message',
    ];

    public function chat(Request $request, $order_id, $user_id){
        // https://bcknd.food2go.online/delivery/chat
        // Keys
        // order_id, user_id

        $chat = $this->chat
        ->where('delivery_id', $request->user()->id)
        ->where('order_id', $order_id)
        ->where('user_id', $user_id)
        ->orderBy('id')
        ->get();
        event(new ChatEvent($chat));

        return response()->json([
            'chat' => $chat
        ]);
    }

    public function store(Request $request){
        // https://bcknd.food2go.online/delivery/chat/send
        // Keys
        // order_id, user_id, message
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'message' => 'required'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $chatRequest = $request->only($this->chatRequest);
        $chatRequest['delivery_id'] = $request->user()->id;
        $chatRequest['user_sender'] = false;
        $message = $this->chat
        ->create($chatRequest);

        return response()->json([
            'message' => $message
        ]);
    }
}
