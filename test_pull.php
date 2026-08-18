<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/api/local-sync/pull', 'GET', [
    'clientId' => 'test_client',
    'since' => '2026-08-16 00:00:00'
]);
$request->headers->set('secret_key', 'Food2go@Sync2024');
$response = app()->handle($request);
echo $response->getContent();
