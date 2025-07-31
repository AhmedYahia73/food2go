<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\kitchen\OrderController;

Route::middleware(['auth:sanctum', 'IsKitchen'])->group(function(){
   Route::controller(OrderController::class)
   ->prefix('orders')->group(function(){
        Route::get('/', 'kitchen_orders');
   });
});

