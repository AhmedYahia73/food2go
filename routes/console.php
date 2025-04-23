<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderDelayEmail;

use App\Models\Order;
use App\Models\OrderNotification;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    $emails = OrderNotification::get();
    $orders = Order::where('order_status', 'pending')
    ->where('created_at', '<', now()->subMinutes(3))
    ->get();
    foreach ($orders as $element) {
        foreach ($emails as $item) {
            Mail::to($item->email)->send(new OrderDelayEmail($element));
        }
    }
})->everyFiveMinutes();