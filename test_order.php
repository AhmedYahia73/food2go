<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\ = \App\Models\Order::orderBy('created_at', 'desc')->take(2)->get();
foreach (\ as \) {
    echo 'ID: ' . \->id . PHP_EOL;
    echo 'Raw Order Number: ' . \->getRawOriginal('order_number') . PHP_EOL;
    echo 'Accessor Order Number: ' . \->order_number . PHP_EOL;
}
