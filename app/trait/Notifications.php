<?php

namespace App\trait;

use Illuminate\Http\Request;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\MulticastSendReport;

trait Notifications
{
    protected $messaging;

    public function sendNotificationToMany(array $tokens, string $title, string $body, array $data = []): MulticastSendReport
    {
        // require kreait/laravel-firebase
        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $this->messaging = $factory->createMessaging();
        $message = CloudMessage::new()
        ->withNotification(Notification::create($title, $body))
        ->withData($data);

        return $this->messaging->sendMulticast($message, $tokens);
    }
}