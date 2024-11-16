<?php

namespace App\Http\Controllers\api\customer\chat;

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
        'delivery_id',
        'message',
    ];

    public function chat(Request $request, $order_id, $delivery_id){
        // https://bcknd.food2go.online/customer/chat/{order_id}/{delivery_id}

        $chat = $this->chat
        ->where('user_id', $request->user()->id)
        ->where('order_id', $order_id)
        ->where('delivery_id', $delivery_id)
        ->orderBy('id')
        ->get();
        event(new ChatEvent($chat));

        return response()->json([
            'chat' => $chat
        ]);
    }

    public function store(Request $request){
        // https://bcknd.food2go.online/customer/chat/send
        // Keys
        // order_id, delivery_id, message
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'delivery_id' => 'required|exists:deliveries,id',
            'message' => 'required'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $chatRequest = $request->only($this->chatRequest);
        $chatRequest['user_id'] = $request->user()->id;
        $chatRequest['user_sender'] = true;
        $message = $this->chat
        ->create($chatRequest);

        return response()->json([
            'message' => $message
        ]);
    }
}
