<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = \App\Models\ChangeLog::where('op', 'update')->orderBy('id', 'desc')->take(5)->get();
echo json_encode($logs, JSON_PRETTY_PRINT);
