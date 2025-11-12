<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\branch\Home\HomeController;
use App\Http\Controllers\api\admin\deal_order\DealOrderController;
use App\Http\Controllers\api\branch\Order\OfferController;
use App\Http\Controllers\api\branch\Order\OnlineOrderController;
use App\Http\Controllers\api\branch\Order\POSOrderController;
use App\Http\Controllers\api\cashier\address\AddressController;
use App\Http\Controllers\api\cashier\customer\CustomerController;
use App\Http\Controllers\api\cashier\reports\CashierReportsController;

use App\Http\Controllers\api\branch\cashier\CashierController;
use App\Http\Controllers\api\branch\cashier\CashierManController;
use App\Http\Controllers\api\branch\delivery\DeliveryController;
use App\Http\Controllers\api\branch\expenses\ExpenseController;
use App\Http\Controllers\api\branch\financial\FinancialController;
use App\Http\Controllers\api\branch\kitchen\KitchenConroller;
use App\Http\Controllers\api\branch\profile\ProfileController;

Route::middleware(['auth:sanctum', 'IsBranch'])->group(function(){
    // 
    Route::controller(CashierController::class)->prefix('cashier')->group(function(){
        Route::get('/', 'view'); 
        Route::put('/status/{id}', 'status'); 
        Route::get('/item/{id}', 'cashier'); 
        Route::post('/add', 'create'); 
        Route::put('/update/{id}', 'modify'); 
        Route::delete('/delete/{id}', 'delete'); 
    });

    Route::controller(CashierManController::class) 
    ->prefix('cashier_man')->group(function(){
        Route::get('/', 'view');
        Route::put('/logout/{id}', 'logout_cashier');
        Route::get('/item/{id}', 'cashier_man');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(DeliveryController::class) 
    ->prefix('delivery')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'delivery');
        Route::get('/history', 'history');
        Route::post('/filter_history', 'filter_history');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(KitchenConroller::class) 
    ->group(function(){
        Route::get('/kitchen', 'view');
        Route::get('/brista', 'brista');
        Route::get('/kitchen/products_in_kitchen/{id}', 'products_in_kitchen');
        Route::get('/kitchen/categories_in_kitchen/{id}', 'categories_in_kitchen');
        Route::get('/kitchen/lists', 'lists');
        Route::post('/kitchen/select_product', 'select_product');
        Route::get('/kitchen/item/{id}', 'kitchen');
        Route::put('/kitchen/status/{id}', 'status');
        Route::post('/kitchen/add', 'create');
        Route::post('/kitchen/update/{id}', 'modify');
        Route::delete('/kitchen/delete/{id}', 'delete');
    });
    
    Route::controller(ExpenseController::class) 
    ->prefix('expense')->group(function(){
        Route::get('/', 'view');
        Route::get('/lists', 'lists');
        Route::post('/add', 'create');
        Route::post('/expenses_report', 'expenses_report');
    });
    
    Route::controller(FinancialController::class) 
    ->prefix('financial')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::get('/item/{id}', 'financial');
        Route::post('/add', 'create');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::controller(ProfileController::class) 
    ->prefix('profile')->group(function(){
        Route::get('/', 'profile');
        Route::post('/update', 'update');
    });
    //_______________________________________________________________________________
    Route::controller(HomeController::class)->prefix('home')->group(function(){
        Route::get('/orders_count', 'home_orders_count');
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
    
    Route::controller(AddressController::class)->prefix('address')->group(function(){
        Route::get('/item/{id}', 'address');
        Route::get('/customer_address/{id}', 'customer_address');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify'); 
    });
    
    Route::controller(CustomerController::class)->prefix('customer')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify'); 
    });
    
    Route::controller(OfferController::class)->prefix('offer')->group(function(){
        Route::post('/check_order', 'check_order');
        Route::post('/approve_offer', 'approve_offer');
    });
    
    Route::controller(OnlineOrderController::class)->prefix('online_order')->group(function(){
        Route::get('/', 'orders');
        Route::get('/count_orders', 'count_orders');
        Route::post('/transfer_branch', 'transfer_branch');
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
    // https://bcknd.food2go.online/branch/pos_order/item/{id}
    // https://bcknd.food2go.online/branch/pos_order/dine_in_table_carts/{id}
    // https://bcknd.food2go.online/branch/pos_order/dine_in_table_order/{id}
    Route::controller(POSOrderController::class)->prefix('pos_order')->group(function(){
        Route::get('/', 'pos_orders');
        Route::get('/customer_data', 'customer_data');
        Route::get('/item/{id}', 'get_order');
        Route::post('/dine_in_order', 'dine_in_order');
        Route::post('/dine_in_payment', 'dine_in_payment');
        Route::put('/tables_status/{id}', 'tables_status');
        Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts');
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order');

        Route::post('/delivery_order', 'delivery_order');
        Route::post('/determine_delivery/{order_id}', 'determine_delivery');
        Route::post('/take_away_order', 'take_away_order');
    });

    Route::controller(CashierReportsController::class)->prefix('branch_cashier_reports')
    ->group(function(){
        Route::post('/', 'branch_cashier_reports');
    });
});

