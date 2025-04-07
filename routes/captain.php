<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\captain_order\make_order\CaptainMakeOrderController;

Route::middleware(['auth:sanctum', 'IsCaptain'])->group(function(){
    Route::controller(CaptainMakeOrderController::class)
    ->group(function(){
        Route::get('/lists', 'lists');
        Route::post('/make_order', 'order');
    });
});