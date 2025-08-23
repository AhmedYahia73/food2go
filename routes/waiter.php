<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\waiter\orders\OrdersController;

Route::middleware(['auth:sanctum', 'IsWaiter'])->group(function(){
    Route::controller(OrdersController::class)
    ->prefix('orders')->group(function(){
        Route::get('/', 'view');
    });
});