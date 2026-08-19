<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \DB::select('SHOW FULL COLUMNS FROM kitchen_orders');
foreach($columns as $c) {
    if ($c->Field === 'order') {
        echo json_encode($c) . "\n";
    }
}
