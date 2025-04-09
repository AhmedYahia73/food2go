<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;

Route::middleware(['auth:sanctum', 'IsCashier'])->group(function(){
    Route::controller(CashierMakeOrderController::class)
    ->group(function(){
        Route::get('/lists', 'lists');
        Route::post('/make_order', 'order');
    });
});