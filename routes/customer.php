<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\customer\home\HomeController;

use App\Http\Controllers\api\customer\offer\OffersController;

use App\Http\Controllers\api\customer\deal\DealController;

use App\Http\Controllers\api\customer\profile\ProfileController;

use App\Http\Controllers\api\customer\otp\OtpController;

use App\Http\Controllers\api\customer\make_order\MakeOrderController;

use App\Http\Controllers\api\customer\chat\ChatController;

use App\Http\Controllers\api\customer\address\AddressController;

use App\Http\Controllers\api\customer\order\OrderController;

use App\Http\Controllers\api\customer\coupon\CouponController;

use App\Http\Controllers\api\customer\order_type\OrderTypeController;


Route::controller(OtpController::class)->prefix('otp')->group(function(){
    Route::post('/create_code', 'create_code');
    Route::post('/check_code', 'check_code');
    Route::post('/change_password', 'change_password');
});

Route::middleware(['auth:sanctum', 'IsCustomer'])->group(function(){
    Route::controller(HomeController::class)->prefix('home')->group(function(){
        Route::post('/', 'products')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::get('/slider', 'slider')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::get('/translation', 'translation')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::get('/web_products', 'web_products')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::post('/filter_product', 'filter_product');
        Route::put('/favourite/{id}', 'favourite');
        Route::get('/fav_products', 'fav_products');
    });

    Route::controller(ChatController::class)->prefix('chat')->group(function(){
        Route::get('/{order_id}/{delivery_id}', 'chat');
        Route::post('/send', 'store');
    });

    Route::controller(CouponController::class)->prefix('coupon')->group(function(){
        Route::post('/', 'coupon');
    });

    Route::controller(AddressController::class)->prefix('address')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'add');
        Route::put('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(MakeOrderController::class)->prefix('make_order')->group(function(){
        Route::post('/', 'order'); 
        Route::any('/callback', 'callback')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::get('/callback_status/{id}', 'callback_status');
        Route::any('/callback_success', 'callback_success')->name('callback_success')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::any('/callback_faild', 'callback_faild')->name('callback_faild')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function(){
        Route::get('/profile_data', 'profile_data');
        Route::post('/update', 'update_profile');
        Route::delete('/delete', 'delete_account');
    });

    Route::controller(OffersController::class)->prefix('offers')->group(function(){
        Route::get('/', 'offers')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::post('/buy_offer', 'buy_offer');
    });

    Route::controller(OrderController::class)->prefix('orders')->group(function(){
        Route::get('/', 'upcomming');
        Route::get('/notification_sound', 'notification_sound');
        Route::get('/history', 'order_history');
        Route::get('/order_status/{id}', 'order_track');
        Route::get('/cancel_time', 'cancel_time');
        Route::put('/cancel/{id}', 'cancel');
    });

    Route::controller(DealController::class)->prefix('deal')->group(function(){
        Route::get('/', 'index')->withOutMiddleware(['auth:sanctum', 'IsCustomer']);
        Route::post('/order', 'order');
    });

    Route::controller(OrderTypeController::class)->prefix('order_type')->group(function(){
        Route::get('/', 'view');
    });
});