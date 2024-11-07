<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\delivery\order\OrderController;

Route::middleware(['auth:sanctum', 'IsDelivery'])->group(function(){
    Route::controller(OrderController::class)->prefix('orders')->group(function(){ 
        Route::get('/', 'orders');
    });
});