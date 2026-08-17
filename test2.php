<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\ = \App\Models\Order::orderBy('created_at', 'desc')->first();
echo 'Raw Order Number: ' . (\->getRawOriginal('order_number') ?? 'NULL') . PHP_EOL;
echo 'ID: ' . \->id . PHP_EOL;
