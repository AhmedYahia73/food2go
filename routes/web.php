<?php

use Illuminate\Support\Facades\Route;
use Reverb\Facades\Reverb;

Reverb::websocket('/orders', function($conn, $msg){
    $conn->send("Hello " . $msg);
});

Route::get('/', function () {
    return view('welcome');
});
