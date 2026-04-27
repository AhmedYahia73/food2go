<?php

use Illuminate\Support\Facades\Route; 
use App\Http\Controllers\api\customer\make_order\MakeOrderGediaController;

Route::get('/', function () {
    return view('welcome');
});

// Geidea payment routes - accessible without authentication
Route::controller(MakeOrderGediaController::class)
->prefix('customer/geidia')->name('customer.')->group(function(){
    Route::get('/callback', 'callback')->name("payment_gedia.callback");
    Route::get('/return', 'return_page')->name("payment_gedia.return");
    Route::get('/page', 'paymentPage')->name("payment_gedia.page");
});
