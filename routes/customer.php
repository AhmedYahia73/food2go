<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\customer\home\HomeController;

use App\Http\Controllers\api\customer\offer\OffersController;

use App\Http\Controllers\api\customer\deal\DealController;

use App\Http\Controllers\api\customer\profile\ProfileController;

use App\Http\Controllers\api\customer\otp\OtpController;

use App\Http\Controllers\api\customer\make_order\MakeOrderController;


Route::controller(OtpController::class)->prefix('otp')->group(function(){
    Route::post('/create_code', 'create_code');
    Route::post('/change_password', 'change_password');
});

Route::middleware(['auth:sanctum', 'IsCustomer'])->group(function(){
    Route::controller(HomeController::class)->prefix('home')->group(function(){
        Route::get('/', 'products');
        Route::post('/filter_product', 'filter_product');
        Route::put('/favourite/{id}', 'favourite');
    });

    Route::controller(MakeOrderController::class)->prefix('make_order')->group(function(){
        Route::post('/', 'order'); 
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function(){
        Route::get('/profile_data', 'profile_data');
        Route::post('/update', 'update_profile');
    });

    Route::controller(OffersController::class)->prefix('offers')->group(function(){
        Route::get('/', 'offers');
        Route::post('/buy_offer', 'buy_offer');
    });

    Route::controller(DealController::class)->prefix('deal')->group(function(){
        Route::get('/', 'index');
        Route::post('/order', 'order');
    });
});