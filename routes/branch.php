<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\branch\Home\HomeController;
use App\Http\Controllers\api\admin\deal_order\DealOrderController;
use App\Http\Controllers\api\branch\Order\OfferController;
use App\Http\Controllers\api\branch\Order\OnlineOrderController;
use App\Http\Controllers\api\branch\Order\POSOrderController;

Route::middleware(['auth:sanctum', 'IsBranch'])->group(function(){
    Route::controller(HomeController::class)->prefix('home')->group(function(){
        Route::get('/', 'home');
    });
    // https://bcknd.food2go.online/branch/home
    // https://bcknd.food2go.online/branch/online_order
    // https://bcknd.food2go.online/branch/online_order/count_orders
    // https://bcknd.food2go.online/branch/online_order/order/{id}
    // https://bcknd.food2go.online/branch/online_order/invoice/{id}
    // https://bcknd.food2go.online/branch/online_order/user_details/{user_id}
    // https://bcknd.food2go.online/branch/online_order/status/{id}
    // keys ===> order_status=confirmed
    // https://bcknd.food2go.online/branch/online_order/notification
    // keys ===> orders=1
    // https://bcknd.food2go.online/branch/online_order/delivery
    // Keys ==> delivery_id, order_id
    // https://bcknd.food2go.online/branch/online_order/order_log
    // Keys ==> order_id
    // https://bcknd.food2go.online/branch/online_order/order_filter_date
    // date, date_to, type
    Route::controller(DealOrderController::class)->prefix('deal')->group(function(){
        Route::post('/', 'deal_order');
        Route::post('/add', 'add');
    });
    
    Route::controller(OfferController::class)->prefix('offer')->group(function(){
        Route::post('/check_order', 'check_order');
        Route::post('/approve_offer', 'approve_offer');
    });
    
    Route::controller(OnlineOrderController::class)->prefix('online_order')->group(function(){
        Route::get('/', 'orders');
        Route::get('/count_orders', 'count_orders');
        Route::get('/order/{id}', 'order');
        Route::get('/invoice/{id}', 'invoice');
        Route::get('/user_details/{id}', 'user_details');
        Route::put('/status/{id}', 'status');
        Route::post('/notification', 'notification');
        Route::post('/delivery', 'delivery');
        Route::post('/order_log', 'order_log');
        Route::post('/order_filter_date', 'order_filter_date');
    });
    // https://bcknd.food2go.online/branch/pos_order
    Route::controller(POSOrderController::class)->prefix('pos_order')->group(function(){
        Route::get('/', 'pos_orders');
        Route::get('/item/{id}', 'get_order');
        Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts');
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');

        Route::post('/delivery_order', 'delivery_order');
        Route::post('/determine_delivery/{order_id}', 'determine_delivery');
        Route::post('/take_away_order', 'take_away_order');
        Route::post('/dine_in_order', 'dine_in_order');
        Route::post('/dine_in_payment', 'dine_in_payment');
        Route::put('/tables_status/{id}', 'tables_status');
    });
});

