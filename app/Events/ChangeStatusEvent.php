<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels; 
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

class ChangeStatusEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    public $status;

    public function __construct($status)
    {
        $this->status = $status;
        Log::info('🎯 New status', ['status' => $status]);
    } 
 
    public function broadcastOn(): array
    {
        return [
            new Channel('newstatus'),
        ];
    }

    public function broadcastAs(): string
    {
        Log::info('📢 Broadcast As: NewstatusEvent');
        return 'NewstatusEvent';
    }

    public function broadcastWith(): array
    {
        $data = [ 
            "status" => $this->status,
        ];
        
        Log::info('📦 Broadcasting Data:', $data);
        
        return $data;
    }
}
