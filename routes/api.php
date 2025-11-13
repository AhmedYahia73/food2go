<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\auth\LoginController;
use App\Http\Controllers\api\auth\SignupController;
use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;

use App\Events\OrderEvent;
use App\Http\Controllers\api\customer\business_setup\BusinessSetupController;

Route::prefix('welcome')->group(function(){
    Route::get('/', function(){
		return view('welcome');
	});
    Route::get('/v1', function(){
        event(new OrderEvent('Hello'));
		return view('welcome');
	});
});

Route::controller(CashierMakeOrderController::class)->group(function(){
    Route::get('sign-qz-request', 'certificate_sign');
});

Route::prefix('store_man/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'store_man');
});

Route::prefix('kitchen/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'kitchen_login');
});

Route::prefix('admin/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'admin_login');
});

Route::prefix('cashier/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'cashier_login');
});

Route::prefix('captain/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'captain_login');
});

Route::prefix('waiter/auth')->controller(LoginController::class)->group(function(){
    Route::post('login', 'waiter_login');
});

Route::prefix('logout')->middleware('auth:sanctum')->controller(LoginController::class)->group(function(){
    Route::post('/', 'logout');
});

Route::prefix('business_setup')->controller(BusinessSetupController::class)->group(function(){
    Route::get('/', 'business_setup');
});

Route::prefix('customer_login')->controller(BusinessSetupController::class)
->group(function(){
    Route::get('/', 'customer_login');
});


Route::prefix('user/auth')->group(function(){
    Route::controller(LoginController::class)->group(function(){
        Route::post('login', 'login');
    });
    Route::controller(SignupController::class)->group(function(){
        Route::post('signup', 'signup');
        Route::post('signup/code', 'code');
        Route::post('signup/phone_code', 'otp_phone');
    });
});

