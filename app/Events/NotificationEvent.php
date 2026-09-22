<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
        $notificationId = is_object($notification) ? ($notification->id ?? null) : $notification;
        Log::info('🎯 New notification event created', ['notification_id' => $notificationId]);
    }
 
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('newNotification'),
        ];
        $branch_ids = $this->notification->branch_ids ?? null;
 

        if ($branch_ids) {
            foreach ($branch_ids as $branch_id) {
                $channels[] = new Channel('newNotification.' . $branch_id);
            }
        }

        Log::info('📢 Broadcasting on channels:', array_map(fn($c) => $c->name, $channels));

        return $channels;
    }

    public function broadcastAs(): string
    {
        Log::info('📢 Broadcast As: NewNotificationEvent');
        return 'NewNotificationEvent';
    }

    public function broadcastWith(): array
    {

        $data = [ 
            "notification" => $this->notification->notification ?? null,
            "is_read" => $this->notification->is_read ?? false,
        ];
        
        Log::info('📦 Broadcasting Data:', $data);
        
        return $data;
    }
}
