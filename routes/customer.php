<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\customer\home\HomeController;

Route::middleware(['auth:sanctum', 'IsCustomer'])->group(function(){
    Route::controller(HomeController::class)->prefix('home')->group(function(){
        Route::get('/', 'products');
    });

});