<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\admin\order\OrderController;

use App\Http\Controllers\api\admin\category\CategoryController;
use App\Http\Controllers\api\admin\category\CreateCategoryController;

use App\Http\Controllers\api\admin\addon\AddonController;

use App\Http\Controllers\api\admin\deal\DealController;

use App\Http\Controllers\api\admin\deal_order\DealOrderController;

use App\Http\Controllers\api\admin\banner\BannerController;

use App\Http\Controllers\api\admin\point_offers\PointOffersController;

use App\Http\Controllers\api\admin\home\HomeController;

use App\Http\Controllers\api\admin\cafe\CafeTablesController;
use App\Http\Controllers\api\admin\cafe\CafeLocationController;

use App\Http\Controllers\api\admin\customer\CustomerController;
use App\Http\Controllers\api\admin\delivery\DeliveryController;
use App\Http\Controllers\api\admin\branch\BranchController;
use App\Http\Controllers\api\admin\admin\AdminController;

use App\Http\Controllers\api\admin\admin_roles\AdminRolesController;

use App\Http\Controllers\api\admin\product\ProductController;
use App\Http\Controllers\api\admin\product\CreateProductController;

use App\Http\Controllers\api\admin\pos\PosOrderController;
use App\Http\Controllers\api\admin\pos\PosCustomerController;
use App\Http\Controllers\api\admin\pos\PosAddressController;
use App\Http\Controllers\api\admin\pos\PosReportsController;

use App\Http\Controllers\api\admin\cashier\CashierController;
use App\Http\Controllers\api\admin\cashier\CashierManController;

use App\Http\Controllers\api\admin\offer_order\OfferOrderController;

use App\Http\Controllers\api\admin\pos\kitchen\KitchenController;
use App\Http\Controllers\api\admin\pos\captain_order\CaptainOrderController;

use App\Http\Controllers\api\admin\payments\PaymentController;

use App\Http\Controllers\api\admin\coupon\CouponController;
use App\Http\Controllers\api\admin\coupon\CreateCouponController;

use App\Http\Controllers\api\admin\settings\ExtraController;
use App\Http\Controllers\api\admin\settings\ExcludeController;
use App\Http\Controllers\api\admin\settings\TaxController;
use App\Http\Controllers\api\admin\settings\DiscountController;
use App\Http\Controllers\api\admin\settings\TranslationController;
use App\Http\Controllers\api\admin\settings\CityController;
use App\Http\Controllers\api\admin\settings\ZoneController;
use App\Http\Controllers\api\admin\settings\SettingController;
use App\Http\Controllers\api\admin\settings\OrderTypeController;
use App\Http\Controllers\api\admin\settings\PaymentMethodController;
use App\Http\Controllers\api\admin\settings\PaymentMethodAutoController;
use App\Http\Controllers\api\admin\settings\MenueController;
use App\Http\Controllers\api\admin\settings\FinancialAccountingController;
use App\Http\Controllers\api\admin\settings\business_setup\CompanyController;
use App\Http\Controllers\api\admin\settings\business_setup\MaintenanceController;
use App\Http\Controllers\api\admin\settings\business_setup\MainBranchesController;
use App\Http\Controllers\api\admin\settings\business_setup\TimeSlotController;
use App\Http\Controllers\api\admin\settings\business_setup\CustomerLoginController;
use App\Http\Controllers\api\admin\settings\business_setup\OrderSettingController;

Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function(){
    Route::controller(OrderController::class)
    ->prefix('order')->group(function(){
        Route::get('/', 'orders');
        Route::get('/count', 'count_orders')->middleware('can:view_order');
        Route::post('/data', 'orders_data')->middleware('can:view_order');
        Route::post('/notification', 'notification')->middleware('can:view_order');
        Route::post('/filter', 'order_filter')->middleware('can:view_order');
        Route::get('/branches', 'branches')->middleware('can:view_order');
        Route::get('/order/{id}', 'order')->middleware('can:view_order');
        Route::get('/invoice/{id}', 'invoice')->middleware('can:view_order');
        Route::put('/status/{id}', 'status')->middleware('can:edit_order');
        Route::post('/delivery', 'delivery')->middleware('can:edit_order');
        Route::get('/user_details/{id}', 'user_details')->middleware('can:view_order');
    });

    Route::controller(HomeController::class)
    ->prefix('home')->group(function(){
        Route::get('/', 'home')->middleware('can:view_home');
    });

    Route::controller(CashierController::class) 
    ->prefix('cashier')->group(function(){
        Route::get('/', 'view')->middleware('can:view_cashier');
        Route::get('/item/{id}', 'cashier')->middleware('can:edit_cashier');
        Route::put('/status/{id}', 'status')->middleware('can:edit_cashier');
        Route::post('/add', 'create')->middleware('can:add_cashier');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_cashier');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_cashier');
    });

    Route::controller(CashierManController::class) 
    ->prefix('cashier_man')->group(function(){
        Route::get('/', 'view')->middleware('can:view_cashier_man');
        Route::get('/item/{id}', 'cashier_man')->middleware('can:edit_cashier_man');
        Route::put('/status/{id}', 'status')->middleware('can:edit_cashier_man');
        Route::post('/add', 'create')->middleware('can:add_cashier_man');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_cashier_man');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_cashier_man');
    });

    Route::controller(KitchenController::class)
    ->prefix('pos/kitchens')->group(function(){
        Route::get('/', 'view')->middleware('can:view_kitchen');
        Route::get('/lists', 'lists')->middleware('can:view_kitchen');
        Route::get('/item/{id}', 'kitchen')->middleware('can:edit_kitchen');
        Route::post('/select_product', 'select_product')->middleware('can:view_kitchen');
        Route::put('/status/{id}', 'status')->middleware('can:edit_kitchen');
        Route::post('/add', 'create')->middleware('can:add_kitchen');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_kitchen');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_kitchen');
    });

    Route::controller(CaptainOrderController::class) 
    ->prefix('pos/captain')->group(function(){
        Route::get('/', 'view')->middleware('can:view_captain');
        Route::get('/item/{id}', 'captain')->middleware('can:edit_captain');
        Route::post('/add', 'create')->middleware('can:add_captain');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_captain');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_captain');
    });

    Route::controller(AdminRolesController::class)
    ->prefix('admin_roles')->group(function(){
        Route::get('/', 'view')->middleware('can:view_admin_roles');
        Route::put('/status/{id}', 'status')->middleware('can:edit_admin_roles');
        Route::post('/add', 'create')->middleware('can:add_admin_roles');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_admin_roles');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_admin_roles');
    });

    Route::controller(TranslationController::class)
    ->prefix('translation')->group(function(){
        Route::get('/', 'view')->middleware('can:view_translation');
        Route::put('/status/{id}', 'status')->middleware('can:edit_translation');
        Route::post('/add', 'create')->middleware('can:add_translation');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_translation');
    });

    Route::controller(BannerController::class)
    ->prefix('banner')->group(function(){
        Route::get('/', 'view')->middleware('can:view_banner');
        Route::get('/item/{id}', 'banner')->middleware('can:edit_banner');
        Route::put('/status/{id}', 'status')->middleware('can:edit_banner');
        Route::post('/add', 'create')->middleware('can:add_banner');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_banner');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_banner');
    }); 

    Route::controller(CafeLocationController::class)
    ->prefix('caffe_location')->group(function(){
        Route::get('/', 'view')->middleware('can:view_cafe_location');
        Route::get('/item/{id}', 'location')->middleware('can:edit_cafe_location');
        Route::post('/add', 'create')->middleware('can:add_cafe_location');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_cafe_location');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_cafe_location');
    });
    
    Route::controller(CafeTablesController::class)
    ->prefix('caffe_tables')->group(function(){
        Route::get('/', 'view')->middleware('can:view_cafe_tables');
        Route::get('/item/{id}', 'table')->middleware('can:edit_cafe_tables');
        Route::put('/status/{id}', 'status')->middleware('can:edit_cafe_tables');
        Route::put('/occupied/{id}', 'occupied')->middleware('can:edit_cafe_tables');
        Route::post('/add', 'create')->middleware('can:add_cafe_tables');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_cafe_tables');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_cafe_tables');
    });
    
    Route::controller(PaymentController::class)
    ->prefix('payment')->group(function(){
        Route::get('/pending', 'pending')->middleware('can:view_payments');
        Route::get('/receipt/{id}', 'receipt')->middleware('can:view_payments');
        Route::get('/history', 'history')->middleware('can:view_payments');
        Route::put('/approve/{id}', 'approve')->middleware('can:status_payments');
        Route::put('/rejected/{id}', 'rejected')->middleware('can:status_payments');
    });

    Route::controller(PointOffersController::class)
    ->prefix('offer')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'offer');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    // Make Deal Module
    Route::controller(DealOrderController::class)->middleware('can:isDealOrder')
    ->prefix('dealOrder')->group(function(){
        Route::post('/', 'deal_order');
        Route::post('/add', 'add');
    });

    Route::controller(OfferOrderController::class)->middleware('can:isOfferOrder')
    ->prefix('offerOrder')->group(function(){
        Route::post('/', 'check_order');
        Route::post('/approve_offer', 'approve_offer');
    });

    // Make Deal Module
    Route::controller(DealController::class)->middleware('can:isDeal')
    ->prefix('deal')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'deal');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(AdminController::class)->middleware('can:isAdmin')
    ->prefix('admin')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'admin');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(BranchController::class)->middleware('can:isBranch')
    ->prefix('branch')->group(function(){
        Route::get('/', 'view');
        Route::get('/branch_product/{id}', 'branch_product');
        Route::get('/branch_options/{id}', 'branch_options');
        Route::put('/branch_product_status/{id}', 'branch_product_status');
        Route::put('/branch_category_status/{id}', 'branch_category_status');
        Route::put('/branch_option_status/{id}', 'branch_option_status');
        Route::get('/item/{id}', 'branch');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(DeliveryController::class)->middleware('can:isDelivery')
    ->prefix('delivery')->group(function(){
    Route::get('/', 'view');
    Route::get('/item/{id}', 'delivery');
        Route::get('/history/{id}', 'history');
        Route::post('/history_filter/{id}', 'filter_history');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(CustomerController::class)->middleware('can:isCustomer')
    ->prefix('customer')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'customer');
        Route::post('/add', 'create');
        Route::put('/status/{id}', 'status');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::prefix('coupon')->middleware('can:isCoupon')->group(function(){
        Route::controller(CouponController::class)->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'coupon');
            Route::put('/status/{id}', 'status');
        });
        Route::controller(CreateCouponController::class)->group(function(){
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
    });
    
    Route::prefix('pos')->group(function(){
        Route::controller(PosCustomerController::class)
        ->prefix('/customer')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
        });
        Route::controller(PosAddressController::class)
        ->prefix('/address')->group(function(){
            Route::get('/item/{id}', 'address');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
        });
        Route::controller(PosReportsController::class)
        ->prefix('/reports')->group(function(){
            Route::get('shift_reports', 'shift_reports');
        });
        Route::controller(PosOrderController::class)
        ->prefix('order')->group(function(){
            Route::get('/lists', 'lists');
            Route::get('/orders', 'pos_orders'); 
            Route::put('/tables_status/{id}', 'tables_status');
        });
    });
    
    Route::prefix('product')->middleware('can:isProduct')->group(function(){
        Route::controller(ProductController::class)->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'product');
            Route::get('/reviews', 'reviews');
        });
        Route::controller(CreateProductController::class)->group(function(){
            Route::post('/add', 'create'); 
            Route::post('/import_excel', 'import_excel'); 
            Route::post('/update/{id}', 'modify'); 
            Route::delete('/delete/{id}', 'delete'); 
        });
    });
    
    Route::prefix('category')->middleware('can:isCategory')->group(function(){
        Route::controller(CategoryController::class)->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'category');
            Route::put('/active/{id}', 'active');
            Route::put('/status/{id}', 'status');
            Route::put('/priority/{id}', 'priority');
        });
        Route::controller(CreateCategoryController::class)->group(function(){
            Route::post('/add', 'create'); 
            Route::post('/update/{id}', 'modify'); 
            Route::delete('/delete/{id}', 'delete'); 
        });
    });

    Route::controller(AddonController::class)->middleware('can:isAddons')
    ->prefix('addons')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'addon');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::prefix('settings')->middleware('can:isSettings')->group(function(){
        Route::controller(ExtraController::class)
        ->prefix('extra')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });

        Route::controller(FinancialAccountingController::class)->prefix('financial')->group(function(){
            Route::get('/', 'view');
            Route::get('item/{id}', 'financial');
            Route::put('status/{id}', 'status');
            Route::post('add', 'create');
            Route::post('update/{id}', 'modify');
            Route::delete('delete/{id}', 'delete');
        });

        Route::controller(MenueController::class)
        ->prefix('menue')->group(function(){
            Route::get('/', 'view')->withOutMiddleware(['auth:sanctum', 'IsAdmin', 'can:isSettings']);
            Route::post('/add', 'create');
            Route::put('/status/{id}', 'status');
            Route::delete('/delete/{id}', 'delete');
        });

        Route::controller(OrderTypeController::class)
        ->prefix('order_type')->group(function(){
            Route::get('/', 'view')->withOutMiddleware(['auth:sanctum', 'IsAdmin', 'can:isSettings']);
            Route::put('/update', 'modify');
        });

        Route::controller(ZoneController::class)
        ->prefix('zone')->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'zone');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::put('/status/{id}', 'status');
            Route::delete('/delete/{id}', 'delete');
        });

        Route::controller(CityController::class)
        ->prefix('city')->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'city');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::put('/status/{id}', 'status');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(ExcludeController::class)
        ->prefix('exclude')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(TaxController::class)
        ->prefix('tax')->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'tax');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(DiscountController::class)
        ->prefix('discount')->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'discount');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(PaymentMethodController::class)
        ->prefix('payment_methods')->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'payment_method');
            Route::put('/status/{id}', 'status');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(PaymentMethodAutoController::class)
        ->prefix('payment_methods_auto')->group(function(){
            Route::get('/', 'view');
            Route::put('/status/{id}', 'status');
            Route::post('/update/{id}', 'modify');
        });

        Route::prefix('business_setup')->group(function(){
            Route::controller(CompanyController::class)
            ->prefix('company')->group(function(){
                Route::get('/', 'view');
                Route::post('/add', 'add');
            });
            
            Route::controller(MaintenanceController::class)
            ->prefix('maintenance')->group(function(){
                Route::get('/', 'view');
                Route::put('/status', 'status');
                Route::post('/add', 'add');
            });

            Route::controller(MainBranchesController::class)
            ->prefix('branch')->group(function(){
                Route::get('/', 'view'); 
                Route::post('/add', 'update'); 
            });

            Route::controller(TimeSlotController::class)
            ->prefix('time_slot')->group(function(){
                Route::get('/', 'view'); 
                Route::post('/add', 'add'); 
            });

            Route::controller(CustomerLoginController::class)
            ->prefix('customer_login')->group(function(){
                Route::get('/', 'view'); 
                Route::post('/add', 'add'); 
            });

            Route::controller(OrderSettingController::class)
            ->prefix('order_setting')->group(function(){
                Route::get('/', 'view'); 
                Route::post('/add', 'add'); 
            });
        });
        
        Route::controller(SettingController::class)
        ->group(function(){
            Route::get('/view_time_cancel', 'view_time_cancel_order');
            Route::post('/update_time_cancel', 'update_time_cancel_order');
            
            Route::get('/resturant_time', 'resturant_time');
            Route::post('/resturant_time_update', 'resturant_time_update');
            
            Route::get('/tax_type', 'tax');
            Route::post('/tax_update', 'tax_update');
            
            Route::get('/delivery_time', 'delivery_time');
            Route::post('/delivery_time_update', 'delivery_time_update');
            
            Route::get('/preparing_time', 'preparing_time');
            Route::post('/preparing_time_update', 'preparing_time_update');
            
            Route::get('/notification_sound', 'notification_sound');
            Route::post('/notification_sound_update', 'notification_sound_update');
        });
    });
});

