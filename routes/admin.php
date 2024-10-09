<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return response()->json([
        'success' => 'You logged success'
    ]);
})->middleware(['auth:sanctum', 'IsAdmin']);

