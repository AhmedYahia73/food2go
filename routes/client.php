<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\client\make_order\ClientMakeOrderController;


Route::controller(ClientMakeOrderController::class)
->prefix('order')->group(function(){
    Route::post('/lists', 'lists');
    Route::post('/dine_in_order', 'dine_in_order');
    Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts');
    Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');
    Route::post('/dine_in_payment', 'dine_in_payment');

    Route::post('/preparing', 'preparing');
    Route::post('/dine_in_split_payment', 'dine_in_split_payment');

});