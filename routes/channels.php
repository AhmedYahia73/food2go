<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chatChannel', function ($chat) {
    return $chat;
}, ['guards' => ['sanctum']]);

Broadcast::channel('new_order', function ($user) {
    return true;
});

Broadcast::channel('print_order', function ($user) {
    return true;
});
