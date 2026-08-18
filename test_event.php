<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\Product::updated(function ($model) {
    echo "IN UPDATED EVENT:\n";
    echo "Attributes: " . json_encode($model->getAttributes()) . "\n";
    echo "Original: " . json_encode($model->getOriginal()) . "\n";
});

$product = \App\Models\Product::first();
echo "BEFORE UPDATE:\n";
echo "Attributes: " . json_encode($product->getAttributes()) . "\n";
echo "Original: " . json_encode($product->getOriginal()) . "\n";

$product->update(['name' => $product->name . ' Test']);
