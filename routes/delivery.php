<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\delivery\order\OrderController;

use App\Http\Controllers\api\delivery\profile\ProfileController;

Route::middleware(['auth:sanctum', 'IsDelivery'])->group(function(){
    Route::controller(OrderController::class)->prefix('orders')->group(function(){ 
        Route::get('/', 'orders');
        Route::put('/status', 'status');
    });
    
    Route::controller(ProfileController::class)->prefix('profile')->group(function(){ 
        Route::get('/profile_data', 'profile_data');
        Route::post('/update', 'update_profile');
    });
});