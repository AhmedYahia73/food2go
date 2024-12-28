<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\auth\LoginController;
use App\Http\Controllers\api\auth\SignupController;

use App\Http\Controllers\api\customer\business_setup\BusinessSetupController;

Route::prefix('admin/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'admin_login');
});

Route::prefix('logout')->middleware('auth:sanctum')->controller(LoginController::class)->group(function(){
    Route::post('/', 'logout');
});

Route::prefix('business_setup')->middleware('auth:sanctum')->controller(BusinessSetupController::class)->group(function(){
    Route::get('/', 'business_setup');
});

Route::prefix('user/auth')->group(function(){
    Route::controller(LoginController::class)->group(function(){
        Route::post('login', 'login');
    });
    Route::controller(SignupController::class)->group(function(){
        Route::post('signup', 'signup');
    });
});

