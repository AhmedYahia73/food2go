<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\customer\home\HomeController;

use App\Http\Controllers\api\customer\offer\OffersController;

use App\Http\Controllers\api\customer\deal\DealController;

Route::middleware(['auth:sanctum', 'IsCustomer'])->group(function(){
    Route::controller(HomeController::class)->prefix('home')->group(function(){
        Route::get('/', 'products');
        Route::put('/favourite/{id}', 'favourite');
    });

    Route::controller(OffersController::class)->prefix('offers')->group(function(){
        Route::get('/', 'offers');
        Route::post('/buy_offer', 'buy_offer');
    });

    Route::controller(DealController::class)->prefix('deal')->group(function(){
        Route::get('/', 'index');
        Route::post('/order', 'order');
    });
});