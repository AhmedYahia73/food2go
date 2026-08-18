<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$maxId = \DB::table('orders')->max('id');
$firstHuge = \DB::table('orders')->where('id', '>', 1000000000)->orderBy('id', 'asc')->first();
$latestNormal = \DB::table('orders')->where('id', '<', 1000000000)->orderBy('id', 'desc')->first();

echo json_encode([
    'max_id' => $maxId, 
    'first_huge_id' => $firstHuge ? $firstHuge->id : null,
    'first_huge_created_at' => $firstHuge ? $firstHuge->created_at : null,
    'latest_normal_id' => $latestNormal ? $latestNormal->id : null,
    'latest_normal_created_at' => $latestNormal ? $latestNormal->created_at : null
], JSON_PRETTY_PRINT);
