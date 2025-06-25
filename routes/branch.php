<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\branch\Home\HomeController;
use App\Http\Controllers\api\branch\Order\DealController;
use App\Http\Controllers\api\branch\Order\OfferController;
use App\Http\Controllers\api\branch\Order\OnlineOrderController;
use App\Http\Controllers\api\branch\Order\POSOrderController;

Route::middleware(['auth:sanctum', 'IsBranch'])->group(function(){
    Route::controller(HomeController::class)->group(function(){

    });
    
    Route::controller(DealController::class)->prefix('deal')->group(function(){

    });
    
    Route::controller(OfferController::class)->prefix('offer')->group(function(){

    });
    
    Route::controller(OnlineOrderController::class)->prefix('online_order')->group(function(){

    });
    
    Route::controller(POSOrderController::class)->prefix('pos_order')->group(function(){

    });
});

