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
        Route::post('/make_order', 'new_order');
        Route::put('/tables_status/{id}', 'tables_status');
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