<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    public $order;

    public function __construct($order)
    {
        $this->order = $order;
        Log::info('🎯 New order', ['order_id' => $order->id]);
    } 
 
    public function broadcastOn(): array
    {
        return [
            new Channel('newOrder'),
        ];
    }

    public function broadcastAs(): string
    {
        Log::info('📢 Broadcast As: NewOrderEvent');
        return 'NewOrderEvent';
    }

    public function broadcastWith(): array
    {
        $data = [ 
            "order_id" => $this->order->id,
        ];
        
        Log::info('📦 Broadcasting Data:', $data);
        
        return $data;
    }
}
