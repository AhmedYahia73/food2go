<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;
use App\Http\Controllers\api\cashier\address\AddressController;
use App\Http\Controllers\api\cashier\customer\CustomerController;
use App\Http\Controllers\api\cashier\Home\HomeController;
use App\Http\Controllers\api\cashier\reports\CashierReportsController;


Route::middleware(['auth:sanctum', 'IsCashier'])->group(function(){
    Route::controller(CashierMakeOrderController::class)
    ->group(function(){
        Route::get('/lists', 'lists');

        Route::get('/get_order/{id}', 'get_order');
        Route::get('/orders', 'pos_orders');
        Route::post('/delivery_order', 'delivery_order')->middleware('can:delivery');
        
        Route::post('/determine_delivery/{order_id}', 'determine_delivery')->middleware('can:delivery');
        Route::post('/printReceipt', 'printReceipt')->withOutMiddleware(['auth:sanctum', 'IsCashier']);

        Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts')->middleware('can:dine_in');
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order')->middleware('can:dine_in');
        Route::post('/dine_in_order', 'dine_in_order')->middleware('can:dine_in');
        Route::post('/dine_in_payment', 'dine_in_payment')->middleware('can:dine_in');

        Route::post('/take_away_order', 'take_away_order')->middleware('can:take_away');
       
        Route::put('/tables_status/{id}', 'tables_status')->middleware('can:table_status');
    }); 
    Route::controller(HomeController::class)
    ->prefix('/home')->group(function(){
        Route::get('/', 'view');
        Route::get('/cashier_data', 'cashier_data');
        Route::put('/active_cashier/{id}', 'active_cashier'); 
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
    Route::controller(AddressController::class)
    ->prefix('/shift_branch_reports')->group(function(){
        Route::get('/item/{id}', 'address');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    }); 
    Route::controller(CashierReportsController::class)
    ->prefix('/reports')->group(function(){
        Route::get('shift_branch', 'shift_branch_reports')->middleware('can:branch_reports');
        Route::get('shift_all_branch', 'shift_reports')->middleware('can:all_reports');

        Route::get('branch_cashiers', 'branch_cashiers')->middleware('can:branch_reports');
        Route::get('all_cashiers', 'all_cashiers')->middleware('can:all_reports');
    }); 
});