<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\captain_order\make_order\CaptainMakeOrderController;
use App\Http\Controllers\api\captain_order\table_order\TableOrderController;

use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;

Route::middleware(['auth:sanctum', 'IsCaptain'])->group(function(){
    Route::controller(CaptainMakeOrderController::class)
    ->group(function(){
        Route::get('/my_lists', 'my_lists');
        Route::get('/my_selection_lists', 'my_selection_lists');
        Route::get('/product_in_category/{id}', 'product_in_category');

        Route::get('/lists', 'lists')->withOutMiddleware(['auth:sanctum', 'IsCaptain']);
        Route::get('/selection_lists', 'my_selection_lists')->withOutMiddleware(['auth:sanctum', 'IsCaptain']);
        Route::get('/get_table_status', 'get_table_status');
        Route::get('/zones_list', 'zones_list')->withOutMiddleware(['auth:sanctum', 'IsCaptain']);
        Route::post('/make_order', 'order');

        Route::post('/dine_in_order', 'dine_in_order'); 
        Route::post('/checkout_request', 'checkout_request');
    });
     
    Route::controller(CashierMakeOrderController::class)
    ->group(function(){
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');
        Route::post('/transfer_order', 'transfer_order');
        Route::post('/preparing', 'preparing');
       
        Route::put('/tables_status/{id}', 'tables_status');
    });
     
    Route::controller(TableOrderController::class)
    ->group(function(){
        Route::post('/merge_table', 'merge_table');
        Route::post('/split_table', 'split_table');
    });
});