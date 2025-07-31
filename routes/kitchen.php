<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\kitchen\OrderController;

Route::middleware(['auth:sanctum', 'IsKitchen'])->group(function(){
   Route::controller(OrderController::class)
   ->prefix('orders')->group(function(){
         Route::get('/', 'kitchen_orders');
         Route::put('/done_status/{id}', 'done_status');
         Route::get('/notification', 'notification');
         Route::put('/read_status/{id}', 'read_status');
   });
});

