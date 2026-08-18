<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = \App\Models\Order::latest('id')->first();
if ($order) {
    echo json_encode(['id' => $order->id, 'order_number' => $order->order_number, 'order_number_type' => gettype($order->order_number)]);
} else {
    echo "No orders";
}
