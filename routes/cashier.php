<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\cashier\make_order\CashierMakeOrderController;
use App\Http\Controllers\api\cashier\make_order\PendingOrderController;
use App\Http\Controllers\api\cashier\address\AddressController;
use App\Http\Controllers\api\cashier\user\AddressController as UserAddressController;
use App\Http\Controllers\api\cashier\customer\CustomerController;
use App\Http\Controllers\api\admin\customer\CustomerController as CustomerAdminController;
use App\Http\Controllers\api\cashier\user\UserController; 
use App\Http\Controllers\api\cashier\Home\HomeController;
use App\Http\Controllers\api\cashier\reports\CashierReportsController;
use App\Http\Controllers\api\admin\deal_order\DealOrderController;
use App\Http\Controllers\api\admin\offer_order\OfferOrderController;
use App\Http\Controllers\api\cashier\profile\ProfileController; 
use App\Http\Controllers\api\cashier\make_order\DiscountController;
use App\Http\Controllers\api\cashier\order\OrderController;
use App\Http\Controllers\api\cashier\expenses_list\ExpensesListController;
use App\Http\Controllers\api\captain_order\make_order\CaptainMakeOrderController;
use App\Http\Controllers\api\cashier\group_products\GroupProductController;
use App\Http\Controllers\api\cashier\delivery_balance\DeliveryBalanceController;
use App\Http\Controllers\api\admin\delivery\SinglePageDeliveryController;
use App\Http\Controllers\api\captain_order\table_order\TableOrderController;

use App\Http\Controllers\api\auth\LoginController;


Route::middleware(['auth:sanctum', 'IsCashier'])->group(function(){
    
    Route::controller(TableOrderController::class)
    ->group(function(){
        Route::post('/merge_table', 'merge_table');
        Route::post('/split_table', 'split_table');
    });

    Route::controller(CaptainMakeOrderController::class)
    ->group(function(){
        Route::get('/captain_orders', 'captain_orders');
        Route::post('/preparation_num', 'preparation_num');
    });
 

    Route::controller(SinglePageDeliveryController::class)
    ->prefix('delivery/single_page')->group(function(){
        Route::get('/orders', 'orders');
        Route::post('/orders_delivery', 'orders_delivery');
    });

    Route::controller(DeliveryBalanceController::class)
    ->prefix('delivery_balance')->group(function(){
        Route::get('/lists', 'lists');
        Route::get('/all_orders', 'orders');
        Route::get('/current_orders', 'current_orders');
        Route::get('/order_history', 'order_history');
        Route::get('/delivery_history', 'delivery_history');

        Route::post('/filter_current_orders', 'filter_current_orders');
        Route::get('/faild_orders', 'faild_orders');
        Route::post('/confirm_faild_order', 'confirm_faild_order');
        Route::post('/pay_orders', 'pay_orders');
        Route::post('/orders_delivery', 'orders_delivery');
    });

    Route::controller(CashierMakeOrderController::class)
    ->group(function(){
        Route::post('/view_user_order', 'view_user_order');
        
        Route::post('/print_takeaway_order', 'print_takeaway_order');

        Route::get('/table_lists', 'table_lists');
        Route::post('/assign_table_captain', 'assign_table_captain');

        Route::post('/tax_module', 'tax_module');
        Route::get('/lists', 'lists');
        Route::get('/status_lists', 'status_lists')->withOutMiddleware(['auth:sanctum', 'IsCashier']);

        Route::post('/discount_module', 'discount_module');

        Route::get('/get_order/{id}', 'get_order');
        Route::get('/orders', 'pos_orders');
        Route::get('/list_due_users', 'list_due_users');
        Route::post('/preparing', 'preparing');

        Route::put('/take_away_status/{id}', 'take_away_status');

        Route::get('/delivery_lists', 'delivery_lists')->middleware('can:delivery');
        Route::post('/delivery_order', 'delivery_order')->middleware('can:delivery');
        Route::put('/order_status/{id}', 'order_status')->middleware('can:delivery');
        Route::post('/determine_delivery/{order_id}', 'determine_delivery')->middleware('can:delivery');
        Route::post('/delivery_cash', 'delivery_cash')->middleware('can:delivery');
        Route::post('/printReceipt', 'printReceipt')->withOutMiddleware(['auth:sanctum', 'IsCashier']);

        Route::get('/dine_in_table_carts/{id}', 'dine_in_table_carts')->middleware('can:dine_in');
        Route::get('/dine_in_table_order/{id}', 'dine_in_table_order')->middleware('can:dine_in');
        Route::post('/dine_in_order', 'dine_in_order')->middleware('can:dine_in');
        Route::post('/dine_in_payment', 'dine_in_payment')->middleware('can:dine_in');
        Route::post('/dine_in_split_payment', 'dine_in_split_payment')->middleware('can:dine_in');
        Route::post('/order_void', 'order_void')->middleware('can:dine_in');
        Route::post('/transfer_order', 'transfer_order')->middleware('can:dine_in');

        Route::post('/take_away_order', 'take_away_order')->middleware('can:take_away');
       
        Route::put('/tables_status/{id}', 'tables_status')->middleware('can:table_status');
    });

    Route::controller(ExpensesListController::class)
    ->prefix("expenses_list")->group(function(){
        Route::get('/', 'view');
        Route::get('/lists', 'lists');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'update');
    });

    Route::controller(OrderController::class)
    ->prefix("orders")->group(function(){
        Route::get('/branches', 'branches');
        Route::get('/notifications', 'notifications');
        Route::get('/order_read/{id}', 'order_read');
        Route::get('/void_order_list', 'void_order_list');
        Route::get('/void_lists', 'void_lists');
        Route::post('/void_order', 'void_order');
        Route::post('/point_of_sale', 'pos_orders');
        Route::get('/order_count', 'order_count');
        Route::get('/online_orders', 'online_orders');
        Route::get('/invoice/{id}', 'invoice');
        Route::get('/order_item/{id}', 'order_item');
        Route::put('/transfer_branch/{id}', 'transfer_branch');
        Route::post('/delivery', 'delivery');
        Route::post('/update_order/{id}', 'update_order');
        Route::put('/status/{id}', 'status');
        Route::get('/order_checkout/{id}', 'order_checkout');
    });

    Route::controller(GroupProductController::class)
    ->prefix("group_product")->group(function(){
        Route::get('/', 'groups_product');
        Route::post('/favourite', 'lists');
        Route::post('/product_in_category/{id}', 'product_category_lists');
    });

    Route::controller(DiscountController::class)
    ->group(function(){
        Route::get('/service_fees', 'service_fees');
        Route::post('/check_discount_code', 'check_discount_code');
    });

    Route::controller(ProfileController::class)
    ->group(function(){
        Route::get('/profile', 'view');
        Route::post('/profile/update', 'update');
        Route::get('/printer', 'printer');
        Route::post('/printer_update', 'printer_update');
    });

    Route::controller(DealOrderController::class)
    ->group(function(){
        Route::post('/deal/deal_order', 'deal_order');
        Route::post('/deal/add', 'add');
        Route::get('/deal/orders', 'orders');
        Route::put('/deal/order_status/{id}', 'order_status');
    });

    Route::controller(OfferOrderController::class)
    ->group(function(){
        Route::post('/offer/check_order', 'check_order');
        Route::post('/offer/approve_offer', 'approve_offer');
    });

    Route::controller(PendingOrderController::class)
    ->group(function(){
        Route::get('/get_pending_orders', 'get_pending_orders')->middleware('can:take_away');
        Route::get('/get_order/{id}', 'get_order')->middleware('can:take_away');
    });

    Route::controller(HomeController::class)
    ->prefix('/home')->group(function(){
        Route::get('/', 'view');
        Route::get('/cashier_data', 'cashier_data');
        Route::put('/active_cashier/{id}', 'active_cashier'); 
    });
    Route::controller(UserController::class)
    ->prefix('/user')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'user');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    });
    Route::controller(CustomerController::class)
    ->prefix('/customer')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    });

    Route::controller(CustomerAdminController::class)
    ->prefix('customer')->group(function(){
        Route::get('/customer_singl_page/{id}', 'single_page');
        Route::get('/due_user', 'due_user');
        Route::post('/single_page_filter/{id}', 'single_page_filter');
        Route::post('/pay_debit', 'pay_debit');
    });
    Route::controller(UserAddressController::class)
    ->prefix('user/address')->group(function(){
        Route::get('/lists', 'lists');
        Route::post('/add/{id}', 'create');
        Route::post('/update/{id}', 'modify'); 
        Route::delete('/delete/{id}', 'delete'); 
        Route::get('/{id}', 'view'); 
    }); 
    Route::controller(AddressController::class)
    ->prefix('/shift_branch_reports')->group(function(){
        Route::get('/item/{id}', 'address');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
    });
    Route::controller(LoginController::class)
    ->prefix('/shift')->group(function(){
        Route::post('/open', 'start_shift');
        Route::get('/close', 'end_shift');
    }); 
    Route::controller(CashierReportsController::class)
    ->prefix('/reports')->group(function(){ 
        Route::post('end_shift_report', 'financial_report');
        Route::post('/manger_report', 'shifts_today');
        Route::post('/captain_report', 'captain_report');
        Route::get('/captain_lists', 'captain_lists');

        Route::post('/order_today', 'order_today');
        Route::get('/filter_fake_order', 'filter_fake_order');
        Route::get('/captain_order_report_instance/{id}', 'captain_order_report_instance');
    }); 
});