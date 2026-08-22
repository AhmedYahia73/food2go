<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$res = Illuminate\Support\Facades\DB::table('translation_tbls')->where('value', 'LIKE', '%سن%')->get();
echo json_encode($res);
?>
