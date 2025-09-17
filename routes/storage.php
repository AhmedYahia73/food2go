<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\storage\PurchaseController;
use App\Http\Controllers\api\storage\StoreController;
use App\Http\Controllers\api\storage\PurchaseCategoryController;
use App\Http\Controllers\api\storage\PurchaseConsumersionController;
use App\Http\Controllers\api\storage\PurchaseProductController;
use App\Http\Controllers\api\storage\PurchaseTransferController;
use App\Http\Controllers\api\storage\WastedController;
use App\Http\Controllers\api\storage\StockController;
use App\Http\Controllers\api\storage\StoreManController;

use App\Http\Controllers\api\cashier\reports\CashierReportsController;

Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function(){
  
    
    Route::controller(PurchaseController::class)
    ->prefix('purchase')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'purchase_item');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(WastedController::class)
    ->prefix('wasted')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'wested');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create'); 
        Route::post('/update/{id}', 'modify'); 
    });
    
    Route::controller(PurchaseTransferController::class)
    ->prefix('purchase_transfer')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/transfer', 'transfer'); 
    });
    
    Route::controller(StoreController::class)
    ->prefix('purchase_stores')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(PurchaseProductController::class)
    ->prefix('purchase_product')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(PurchaseCategoryController::class)
    ->prefix('purchase_categories')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'category');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    }); 
    
    Route::controller(PurchaseConsumersionController::class)
    ->prefix('purchase_consumersion')->group(function(){
        Route::get('/', 'view'); 
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(StoreManController::class)
    ->prefix('purchase_store_man')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
});

