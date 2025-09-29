<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\client\make_order\ClientMakeOrderController;
use App\Http\Controllers\api\client\waiter_call\WaiterCallController;

use App\Http\Controllers\api\customer\home\HomeController;

Route::controller(ClientMakeOrderController::class)
->prefix('order')->group(function(){
    Route::get('/products', 'products');
    Route::post('/lists', 'lists');
    Route::post('/dine_in_order', 'dine_in_order');
    Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts');
    Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');
    Route::post('/dine_in_payment', 'dine_in_payment');
    Route::post('/dine_in_split_payment', 'dine_in_split_payment');
});

Route::controller(WaiterCallController::class)->group(function(){
    Route::post('/call_waiter', 'call_waiter');
    Route::post('/call_payment', 'call_payment');
});

Route::controller(HomeController::class)->group(function(){
    Route::get('/menue', 'menue');
});