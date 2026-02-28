<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\client\make_order\ClientMakeOrderController;
use App\Http\Controllers\api\client\waiter_call\WaiterCallController;

use App\Http\Controllers\api\customer\home\HomeController;

Route::controller(ClientMakeOrderController::class)
->prefix('order')->group(function(){
    Route::get('/products/{id}', 'products');
    Route::post('/lists', 'lists');
    Route::post('/dine_in_order', 'dine_in_order');
    Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts');
    Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');
    Route::post('/dine_in_payment', 'dine_in_payment');
    Route::post('/dine_in_split_payment', 'dine_in_split_payment'); 
});

Route::controller(ClientMakeOrderController::class)
->prefix('home')->group(function(){
    Route::post('/recommandation_product', 'favourit_product');
    Route::post('/discount_product', 'discount_product');
    Route::post('/products_in_category/{id}', 'products_in_category');
    Route::post('/categories', 'category');
});

Route::controller(WaiterCallController::class)->group(function(){
    Route::post('/call_waiter', 'call_waiter');
    Route::post('/call_captain_order', 'call_captain_order');
    Route::post('/captain_call_payment', 'captain_call_payment');
    Route::post('/call_payment', 'call_payment');
    Route::post('/call_captain_order', 'call_captain_order');
    Route::post('/cancel_call_pyment', 'cancel_call_pyment');
});

Route::controller(HomeController::class)->group(function(){
    Route::get('/menue', 'menue');
});