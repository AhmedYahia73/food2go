<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\admin\order\OrderController;

use App\Http\Controllers\api\admin\category\CategoryController;
use App\Http\Controllers\api\admin\category\CreateCategoryController;

use App\Http\Controllers\api\admin\addon\AddonController;

use App\Http\Controllers\api\admin\settings\ExtraController;

Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function(){
    Route::controller(OrderController::class)->prefix('order')->group(function(){
        Route::get('/categories', 'categories');
    });
    
    Route::prefix('category')->group(function(){
        Route::controller(CategoryController::class)->group(function(){
            Route::get('/', 'view');
            Route::put('/status/{id}', 'status');
            Route::put('/priority/{id}', 'priority');
        });
        Route::controller(CreateCategoryController::class)->group(function(){
            Route::post('/add', 'create'); 
            Route::put('/update/{id}', 'modify'); 
            Route::delete('/delete/{id}', 'delete'); 
        });
    });

    Route::controller(AddonController::class)->prefix('addons')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::put('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::prefix('settings')->group(function(){
        Route::controller(ExtraController::class)->prefix('extra')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::put('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
    });
});

