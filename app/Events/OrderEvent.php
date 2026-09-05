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
        $orderId = is_object($order) ? ($order->id ?? null) : $order;
        Log::info('🎯 New order event created', ['order_id' => $orderId]);
    } 
 
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('newOrder'),
        ];

        $branchId = null;
        if (is_object($this->order)) {
            $branchId = $this->order->branch_id ?? null;
            if (!$branchId && !empty($this->order->address_id)) {
                $branchId = \App\Models\Address::find($this->order->address_id)?->zone?->branch_id;
            }
        } elseif (is_numeric($this->order)) {
            $orderModel = \App\Models\Order::find($this->order);
            $branchId = $orderModel?->branch_id;
        }

        if ($branchId) {
            $channels[] = new Channel('newOrder.' . $branchId);
        }

        Log::info('📢 Broadcasting on channels:', array_map(fn($c) => $c->name, $channels));

        return $channels;
    }

    public function broadcastAs(): string
    {
        Log::info('📢 Broadcast As: NewOrderEvent');
        return 'NewOrderEvent';
    }

    public function broadcastWith(): array
    {
        $orderId = is_object($this->order) ? ($this->order->id ?? null) : $this->order;
        $branchId = null;
        if (is_object($this->order)) {
            $branchId = $this->order->branch_id ?? null;
            if (!$branchId && !empty($this->order->address_id)) {
                $branchId = \App\Models\Address::find($this->order->address_id)?->zone?->branch_id;
            }
        } elseif (is_numeric($orderId)) {
            $branchId = \App\Models\Order::where('id', $orderId)->value('branch_id');
        }

        $data = [ 
            "order_id" => $orderId,
            "branch_id" => $branchId,
        ];
        
        Log::info('📦 Broadcasting Data:', $data);
        
        return $data;
    }
}
