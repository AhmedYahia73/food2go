<?php

namespace App\Http\Controllers\api\customer\chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Chat;

class ChatController extends Controller
{
    public function __construct(private Chat $chat){}
    protected $chatRequest = [
        'order_id',
        'delivery_id',
        'message',
    ];

    public function chat(Request $request){
        // https://bcknd.food2go.online/customer/chat
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'delivery_id' => 'required|exists:deliveries,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $chat = $this->chat
        ->where('user_id', $request->user()->id)
        ->where('order_id', $request->order_id)
        ->where('delivery_id', $request->delivery_id)
        ->orderBy('id')
        ->get();

        return response()->json([
            'chat' => $chat
        ]);
    }

    public function store(Request $request){
        // https://bcknd.food2go.online/customer/chat/send
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