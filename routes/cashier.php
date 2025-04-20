<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;
use App\Http\Controllers\api\cashier\address\AddressController;
use App\Http\Controllers\api\cashier\customer\CustomerController; 

Route::middleware(['auth:sanctum', 'IsCashier'])->group(function(){
    Route::controller(CashierMakeOrderController::class)
    ->group(function(){
        Route::get('/lists', 'lists');
        Route::get('/orders', 'pos_orders');
        Route::get('/get_order/{id}', 'get_order');
        Route::post('/delivery_order', 'delivery_order');
        Route::post('/determine_delivery/{order_id}', 'determine_delivery');
        Route::post('/printReceipt', 'printReceipt')->withOutMiddleware(['auth:sanctum', 'IsCashier']);

        Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts');
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');
        Route::post('/dine_in_order', 'dine_in_order');
        Route::post('/dine_in_payment', 'dine_in_payment');

        Route::post('/take_away_order', 'take_away_order');
       // Route::put('/tables_status/{id}', 'tables_status');
    }); 
    Route::controller(CustomerController::class)
    ->prefix('/customer')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    });
    Route::controller(AddressController::class)
    ->prefix('/address')->group(function(){
        Route::get('/item/{id}', 'address');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    }); 
});