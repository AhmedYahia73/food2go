<?php

use Illuminate\Support\Facades\Route;
use Reverb\Facades\Reverb;

Reverb::websocket('/', function(){
    echo 'success';
});

Route::get('/', function () {
    return view('welcome');
});
