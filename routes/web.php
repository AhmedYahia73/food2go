<?php

use Illuminate\Support\Facades\Route; 
use App\Http\Controllers\api\customer\make_order\MakeOrderGediaController;

Route::get('/', function () {
    return view('welcome');
});

