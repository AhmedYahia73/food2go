<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = \App\Models\Product::first();
echo "Product ID: " . $product->id . "\n";
echo "Old Status: " . $product->status . "\n";
$newStatus = $product->status == 1 ? 0 : 1;
$product->update(['status' => $newStatus]);
echo "New Status: " . $product->status . "\n";

$log = \App\Models\ChangeLog::where('table_name', 'products')
    ->where('record_id', $product->id)
    ->orderBy('id', 'desc')
    ->first();
if ($log) {
    echo "ChangeLog Found!\n";
    echo "Op: " . $log->op . "\n";
    echo "Created At: " . $log->created_at . "\n";
    echo "Old Payload: " . json_encode($log->old_payload) . "\n";
    echo "New Payload: " . json_encode($log->new_payload) . "\n";
} else {
    echo "No ChangeLog found!\n";
}
