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
        $branch_ids = null;
        if (is_object($this->notification)) {
            $branch_ids = $this->notification->branch_ids ?? null;
        } elseif (is_array($this->notification)) {
            $branch_ids = $this->notification['branch_ids'] ?? null;
        }

        if (!empty($branch_ids) && is_iterable($branch_ids)) {
            foreach ($branch_ids as $branch_id) {
                if (!empty($branch_id)) {
                    $channels[] = new Channel('newNotification.' . $branch_id);
                }
            }
        } else {
            // General notification: broadcast to all branches so any branch user receives it
            try {
                $allBranchIds = \App\Models\Branch::pluck('id')->toArray();
                foreach ($allBranchIds as $branch_id) {
                    if (!empty($branch_id)) {
                        $channels[] = new Channel('newNotification.' . $branch_id);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Could not fetch branch IDs for general broadcast: ' . $e->getMessage());
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
        $notification = $this->notification;
        $notificationId = null;
        $text = null;
        $isRead = false;
        $createdAt = now()->toISOString();
        $branchIds = [];

        if (is_object($notification)) {
            $notificationId = $notification->id ?? null;
            $text = $notification->notification ?? null;
            $isRead = (bool)($notification->is_read ?? false);
            if (!empty($notification->created_at)) {
                $createdAt = is_string($notification->created_at) ? $notification->created_at : $notification->created_at->toISOString();
            }
            $branchIds = $notification->branch_ids ?? [];
        } elseif (is_array($notification)) {
            $notificationId = $notification['id'] ?? null;
            $text = $notification['notification'] ?? ($notification['message'] ?? null);
            $isRead = (bool)($notification['is_read'] ?? false);
            $createdAt = $notification['created_at'] ?? now()->toISOString();
            $branchIds = $notification['branch_ids'] ?? [];
        } else {
            $text = (string)$notification;
        }

        $data = [ 
            "id" => $notificationId,
            "notification" => $text,
            "is_read" => $isRead,
            "created_at" => $createdAt,
            "branch_ids" => is_array($branchIds) ? $branchIds : [],
        ];
        
        Log::info('📦 Broadcasting Data:', $data);
        
        return $data;
    }
}
