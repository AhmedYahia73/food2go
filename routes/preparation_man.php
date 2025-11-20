<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\preparation_man\PreparationController;
// preparation_man
Route::middleware(['auth:sanctum', 'IsPreparation'])->group(function(){
   Route::controller(PreparationController::class)
   ->prefix('orders')->group(function(){
         Route::get('/', 'preparation_orders');
         Route::put('/preparation_status/{id}', 'preparation_status');
         Route::get('/notification', 'notification');
         Route::put('/read_status/{id}', 'read_status');
   });
});
