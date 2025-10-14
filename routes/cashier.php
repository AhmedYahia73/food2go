<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;
use App\Http\Controllers\api\cashier\make_order\PendingOrderController;
use App\Http\Controllers\api\cashier\address\AddressController;
use App\Http\Controllers\api\cashier\user\AddressController as UserAddressController;
use App\Http\Controllers\api\cashier\customer\CustomerController;
use App\Http\Controllers\api\cashier\user\UserController; 
use App\Http\Controllers\api\cashier\Home\HomeController;
use App\Http\Controllers\api\cashier\reports\CashierReportsController;
use App\Http\Controllers\api\admin\deal_order\DealOrderController;
use App\Http\Controllers\api\admin\offer_order\OfferOrderController;
use App\Http\Controllers\api\auth\LoginController;


Route::middleware(['auth:sanctum', 'IsCashier'])->group(function(){
    Route::controller(CashierMakeOrderController::class)
    ->group(function(){
        Route::get('/lists', 'lists');

        Route::get('/get_order/{id}', 'get_order');
        Route::get('/orders', 'pos_orders');
        Route::post('/preparing', 'preparing');

        Route::put('/take_away_status/{id}', 'take_away_status');

        Route::get('/delivery_lists', 'delivery_lists')->middleware('can:delivery');
        Route::post('/delivery_order', 'delivery_order')->middleware('can:delivery');
        Route::put('/order_status/{id}', 'order_status')->middleware('can:delivery');
        Route::post('/determine_delivery/{order_id}', 'determine_delivery')->middleware('can:delivery');
        Route::post('/delivery_cash', 'delivery_cash')->middleware('can:delivery');
        Route::post('/printReceipt', 'printReceipt')->withOutMiddleware(['auth:sanctum', 'IsCashier']);

        Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts')->middleware('can:dine_in');
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order')->middleware('can:dine_in');
        Route::post('/dine_in_order', 'dine_in_order')->middleware('can:dine_in');
        Route::post('/dine_in_payment', 'dine_in_payment')->middleware('can:dine_in');
        Route::post('/dine_in_split_payment', 'dine_in_split_payment')->middleware('can:dine_in');
        Route::post('/order_void', 'order_void')->middleware('can:dine_in');
        Route::post('/transfer_order', 'transfer_order')->middleware('can:dine_in');

        Route::post('/take_away_order', 'take_away_order')->middleware('can:take_away');
       
        Route::put('/tables_status/{id}', 'tables_status')->middleware('can:table_status');
    });

    Route::controller(DealOrderController::class)
    ->group(function(){
        Route::post('/deal/deal_order', 'deal_order');
        Route::post('/deal/add', 'add');
        Route::post('/deal/orders', 'orders');
        Route::put('/deal/order_status/{id}', 'order_status');
    });

    Route::controller(OfferOrderController::class)
    ->group(function(){
        Route::post('/offer/check_order', 'check_order');
        Route::post('/offer/approve_offer', 'approve_offer');
    });

    Route::controller(PendingOrderController::class)
    ->group(function(){
        Route::get('/get_pending_orders', 'get_pending_orders')->middleware('can:take_away');
        Route::get('/get_order/{id}', 'get_order')->middleware('can:take_away');
    });

    Route::controller(HomeController::class)
    ->prefix('/home')->group(function(){
        Route::get('/', 'view');
        Route::get('/cashier_data', 'cashier_data');
        Route::put('/active_cashier/{id}', 'active_cashier'); 
    });
    Route::controller(UserController::class)
    ->prefix('/user')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'user');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    });
    Route::controller(CustomerController::class)
    ->prefix('/customer')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    });
    Route::controller(UserAddressController::class)
    ->prefix('user/address')->group(function(){
        Route::get('/lists', 'lists');
        Route::post('/add/{id}', 'create');
        Route::post('/update/{id}', 'modify'); 
        Route::delete('/delete/{id}', 'delete'); 
        Route::get('/{id}', 'view'); 
    }); 
    Route::controller(AddressController::class)
    ->prefix('/shift_branch_reports')->group(function(){
        Route::get('/item/{id}', 'address');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    }); // lists,
    Route::controller(LoginController::class)
    ->prefix('/shift')->group(function(){
        Route::post('/open', 'start_shift');
        Route::get('/close', 'end_shift');
    }); 
    Route::controller(CashierReportsController::class)
    ->prefix('/reports')->group(function(){
        Route::get('shift_branch', 'shift_branch_reports')->middleware('can:branch_reports');
        Route::get('shift_all_branch', 'shift_reports')->middleware('can:all_reports');
        Route::get('cashier_reports', 'cashier_reports')->middleware('can:all_reports');

        Route::get('branch_cashiers', 'branch_cashiers')->middleware('can:branch_reports');
        Route::get('all_cashiers', 'all_cashiers')->middleware('can:all_reports');
    }); 
});