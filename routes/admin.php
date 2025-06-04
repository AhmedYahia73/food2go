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

use App\Http\Controllers\api\admin\Group\GroupController;
use App\Http\Controllers\api\admin\ExtraGroup\ExtraGroupController;

use App\Http\Controllers\api\admin\coupon\CouponController;
use App\Http\Controllers\api\admin\coupon\CreateCouponController;

use App\Http\Controllers\api\admin\settings\ScheduleSlotController;
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
use App\Http\Controllers\api\admin\settings\business_setup\OrderNotificationController;

Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function(){
    Route::controller(GroupController::class)
    ->prefix('group')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'group');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(ExtraGroupController::class)
    ->prefix('extra_group')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'group');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(OrderController::class)
    ->prefix('order')->group(function(){
        Route::get('/', 'orders');
        Route::post('/log', 'order_log')->middleware('can:log_order');
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
        Route::post('/order_filter_date', 'order_filter_date')->middleware('can:view_order');
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
        Route::get('/', 'view')->middleware('can:view_point_offers');
        Route::get('/item/{id}', 'offer')->middleware('can:edit_point_offers');
        Route::post('/add', 'create')->middleware('can:add_point_offers');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_point_offers');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_point_offers');
    });

    // Make Deal Module
    Route::controller(DealOrderController::class)
    ->prefix('dealOrder')->group(function(){
        Route::post('/', 'deal_order')->middleware('can:view_deal_order');
        Route::post('/add', 'add')->middleware('can:add_deal_order');
    });

    Route::controller(OfferOrderController::class)
    ->prefix('offerOrder')->group(function(){
        Route::post('/', 'check_order')->middleware('can:approve_offer_order');
        Route::post('/approve_offer', 'approve_offer')->middleware('can:approve_offer_order');
    });

    // Make Deal Module
    Route::controller(DealController::class)
    ->prefix('deal')->group(function(){
        Route::get('/', 'view')->middleware('can:view_deal');
        Route::get('/item/{id}', 'deal')->middleware('can:edit_deal');
        Route::put('/status/{id}', 'status')->middleware('can:edit_deal');
        Route::post('/add', 'create')->middleware('can:add_deal');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_deal');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_deal');
    });

    Route::controller(AdminController::class)
    ->prefix('admin')->group(function(){
        Route::get('/', 'view')->middleware('can:view_admin');
        Route::get('/item/{id}', 'admin')->middleware('can:edit_admin');
        Route::put('/status/{id}', 'status')->middleware('can:edit_admin');
        Route::post('/add', 'create')->middleware('can:add_admin');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_admin');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_admin');
    });

    Route::controller(BranchController::class)
    ->prefix('branch')->group(function(){
        Route::get('/', 'view')->middleware('can:view_branch');
        Route::get('/branch_in_product/{id}', 'branch_in_product')->middleware('can:product_branch');
        Route::get('/branch_product/{id}', 'branch_product')->middleware('can:product_branch');
        Route::get('/branch_options/{id}', 'branch_options')->middleware('can:option_branch');
        Route::put('/branch_product_status/{id}', 'branch_product_status')->middleware('can:product_branch');
        Route::put('/branch_category_status/{id}', 'branch_category_status')->middleware('can:category_branch');
        Route::put('/branch_option_status/{id}', 'branch_option_status')->middleware('can:option_branch');
        Route::get('/item/{id}', 'branch')->middleware('can:edit_branch');
        Route::put('/status/{id}', 'status')->middleware('can:edit_branch');
        Route::post('/add', 'create')->middleware('can:add_branch');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_branch');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_branch');
    });

    Route::controller(DeliveryController::class)
    ->prefix('delivery')->group(function(){
        Route::get('/', 'view')->middleware('can:view_delivery');
        Route::get('/item/{id}', 'delivery')->middleware('can:edit_delivery');
        Route::get('/history/{id}', 'history')->middleware('can:view_delivery');
        Route::post('/history_filter/{id}', 'filter_history')->middleware('can:view_delivery');
        Route::put('/status/{id}', 'status')->middleware('can:edit_delivery');
        Route::post('/add', 'create')->middleware('can:add_delivery');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_delivery');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_delivery');
    });

    Route::controller(CustomerController::class)
    ->prefix('customer')->group(function(){
        Route::get('/', 'view')->middleware('can:view_customer');
        Route::get('/item/{id}', 'customer')->middleware('can:edit_customer');
        Route::post('/add', 'create')->middleware('can:add_customer');
        Route::put('/status/{id}', 'status')->middleware('can:edit_customer');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_customer');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_customer');
    });
    
    Route::prefix('coupon')->group(function(){
        Route::controller(CouponController::class)->group(function(){
            Route::get('/', 'view')->middleware('can:view_coupon');
            Route::get('/item/{id}', 'coupon')->middleware('can:edit_coupon');
            Route::put('/status/{id}', 'status')->middleware('can:edit_coupon');
        });
        Route::controller(CreateCouponController::class)->group(function(){
            Route::post('/add', 'create')->middleware('can:add_coupon');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_coupon');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_coupon');
        });
    });
    
    Route::prefix('pos')->group(function(){
        Route::controller(PosCustomerController::class)
        ->prefix('/customer')->group(function(){
            Route::get('/', 'view')->middleware('can:view_pos_customer');
            Route::post('/add', 'create')->middleware('can:add_pos_customer');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_pos_customer');
        });
        Route::controller(PosAddressController::class)
        ->prefix('/address')->group(function(){
            Route::get('/item/{id}', 'address')->middleware('can:view_pos_address');
            Route::post('/add', 'create')->middleware('can:add_pos_address');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_pos_address');
        });
        Route::controller(PosReportsController::class)
        ->prefix('/reports')->group(function(){
            Route::get('shift_reports', 'shift_reports')->middleware('can:view_pos_reports');
        });
        Route::controller(PosOrderController::class)
        ->prefix('order')->group(function(){
            Route::get('/lists', 'lists')->middleware('can:view_pos_order');
            Route::get('/orders', 'pos_orders')->middleware('can:view_pos_order'); 
            Route::put('/tables_status/{id}', 'tables_status')->middleware('can:status_pos_table');
        });
    });
    
    Route::prefix('product')->group(function(){
        Route::controller(ProductController::class)->group(function(){
            Route::get('/', 'view')->middleware('can:view_product');
            Route::get('/item/{id}', 'product')->middleware('can:edit_product');
            Route::get('/reviews', 'reviews')->middleware('can:view_product');
        });
        Route::controller(CreateProductController::class)->group(function(){
            Route::post('/add', 'create')->middleware('can:add_product'); 
            Route::post('/import_excel', 'import_excel')->middleware('can:edit_product'); 
            Route::post('/update/{id}', 'modify')->middleware('can:edit_product'); 
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_product'); 
        });
    });
    
    Route::prefix('category')->group(function(){
        Route::controller(CategoryController::class)->group(function(){
            Route::get('/', 'view')->middleware('can:view_category');
            Route::get('/item/{id}', 'category')->middleware('can:edit_category');
            Route::put('/active/{id}', 'active')->middleware('can:edit_category');
            Route::put('/status/{id}', 'status')->middleware('can:edit_category');
            Route::put('/priority/{id}', 'priority')->middleware('can:edit_category');
        });
        Route::controller(CreateCategoryController::class)->group(function(){
            Route::post('/add', 'create')->middleware('can:add_category'); 
            Route::post('/update/{id}', 'modify')->middleware('can:edit_category'); 
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_category'); 
        });
    });

    Route::controller(AddonController::class)
    ->prefix('addons')->group(function(){
        Route::get('/', 'view')->middleware('can:view_addons');
        Route::get('/item/{id}', 'addon')->middleware('can:edit_addons');
        Route::post('/add', 'create')->middleware('can:add_addons');
        Route::post('/update/{id}', 'modify')->middleware('can:edit_addons');
        Route::delete('/delete/{id}', 'delete')->middleware('can:delete_addons');
    });

    Route::prefix('settings')->group(function(){
        Route::controller(ExtraController::class)
        ->prefix('extra')->group(function(){
            Route::get('/', 'view')->middleware('can:view_extra');
            Route::post('/add', 'create')->middleware('can:add_extra');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_extra');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_extra');
        });

        Route::controller(FinancialAccountingController::class)->prefix('financial')->group(function(){
            Route::get('/', 'view')->middleware('can:view_financial_accounting');
            Route::get('item/{id}', 'financial')->middleware('can:edit_financial_accounting');
            Route::put('status/{id}', 'status')->middleware('can:edit_financial_accounting');
            Route::post('add', 'create')->middleware('can:add_financial_accounting');
            Route::post('update/{id}', 'modify')->middleware('can:edit_financial_accounting');
            Route::delete('delete/{id}', 'delete')->middleware('can:delete_financial_accounting');
        });

        Route::controller(ScheduleSlotController::class)->prefix('schedule_time_slot')->group(function(){
            Route::get('/', 'view');
            Route::get('item/{id}', 'schedule_time_slot');
            Route::put('status/{id}', 'status');
            Route::post('add', 'create');
            Route::post('update/{id}', 'modify');
            Route::delete('delete/{id}', 'delete');
        });

        Route::controller(MenueController::class)
        ->prefix('menue')->group(function(){
            Route::get('/', 'view')->withOutMiddleware(['auth:sanctum', 'IsAdmin', 'can:isSettings']);
            Route::post('/add', 'create')->middleware('can:add_menue');
            Route::put('/status/{id}', 'status')->middleware('can:status_menue');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_menue');
        });

        Route::controller(OrderTypeController::class)
        ->prefix('order_type')->group(function(){
            Route::get('/', 'view')->withOutMiddleware(['auth:sanctum', 'IsAdmin', 'can:isSettings']);
            Route::put('/update', 'modify')->middleware('can:edit_order_type');
        });

        Route::controller(ZoneController::class)
        ->prefix('zone')->group(function(){
            Route::get('/', 'view')->middleware('can:view_zone');
            Route::get('/item/{id}', 'zone')->middleware('can:edit_zone');
            Route::post('/add', 'create')->middleware('can:add_zone');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_zone');
            Route::put('/status/{id}', 'status')->middleware('can:edit_zone');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_zone');
        });

        Route::controller(CityController::class)
        ->prefix('city')->group(function(){
            Route::get('/', 'view')->middleware('can:view_city');
            Route::get('/item/{id}', 'city')->middleware('can:edit_city');
            Route::post('/add', 'create')->middleware('can:add_city');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_city');
            Route::put('/status/{id}', 'status')->middleware('can:edit_city');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_city');
        });
        
        Route::controller(ExcludeController::class)
        ->prefix('exclude')->group(function(){
            Route::get('/', 'view')->middleware('can:view_exclude');
            Route::post('/add', 'create')->middleware('can:add_exclude');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_exclude');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_exclude');
        });
        
        Route::controller(TaxController::class)
        ->prefix('tax')->group(function(){
            Route::get('/', 'view')->middleware('can:view_tax');
            Route::get('/item/{id}', 'tax')->middleware('can:edit_tax');
            Route::post('/add', 'create')->middleware('can:add_tax');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_tax');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_tax');
        });
        
        Route::controller(DiscountController::class)
        ->prefix('discount')->group(function(){
            Route::get('/', 'view')->middleware('can:view_discount');
            Route::get('/item/{id}', 'discount')->middleware('can:edit_discount');
            Route::post('/add', 'create')->middleware('can:add_discount');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_discount');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_discount');
        });
        
        Route::controller(PaymentMethodController::class)
        ->prefix('payment_methods')->group(function(){
            Route::get('/', 'view')->middleware('can:view_payment_method');
            Route::get('/item/{id}', 'payment_method')->middleware('can:edit_payment_method');
            Route::put('/status/{id}', 'status')->middleware('can:edit_payment_method');
            Route::post('/add', 'create')->middleware('can:add_payment_method');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_payment_method');
            Route::delete('/delete/{id}', 'delete')->middleware('can:delete_payment_method');
        });
        
        Route::controller(PaymentMethodAutoController::class)
        ->prefix('payment_methods_auto')->group(function(){
            Route::get('/', 'view')->middleware('can:view_payment_method_auto');
            Route::put('/status/{id}', 'status')->middleware('can:status_payment_method_auto');
            Route::post('/update/{id}', 'modify')->middleware('can:edit_payment_method_auto');
        });

        Route::prefix('business_setup')->group(function(){
            Route::controller(CompanyController::class)
            ->prefix('company')->group(function(){
                Route::get('/', 'view')->middleware('can:view_company_info');
                Route::post('/add', 'add')->middleware('can:edit_company_info');
            });
            
            Route::controller(MaintenanceController::class)
            ->prefix('maintenance')->group(function(){
                Route::get('/', 'view')->middleware('can:view_maintenance');
                Route::put('/status', 'status')->middleware('can:add_maintenance');
                Route::post('/add', 'add')->middleware('can:add_maintenance');
            });

            Route::controller(MainBranchesController::class)
            ->prefix('branch')->group(function(){
                Route::get('/', 'view')->middleware('can:view_main_branch');
                Route::post('/add', 'update')->middleware('can:edit_main_branch'); 
            });

            Route::controller(TimeSlotController::class)
            ->prefix('time_slot')->group(function(){
                Route::get('/', 'view')->middleware('can:view_time_slot');
                Route::post('/add_custom', 'add_custom')->middleware('can:edit_time_slot'); 
                Route::post('/add_times', 'add_times')->middleware('can:edit_time_slot'); 
                Route::post('/update_times/{id}', 'update_times')->middleware('can:edit_time_slot'); 
            });

            Route::controller(CustomerLoginController::class)
            ->prefix('customer_login')->group(function(){
                Route::get('/', 'view')->middleware('can:view_customer_login');
                Route::post('/add', 'add')->middleware('can:edit_customer_login'); 
            });

            Route::controller(OrderSettingController::class)
            ->prefix('order_setting')->group(function(){
                Route::get('/', 'view')->middleware('can:view_order_settings');
                Route::post('/add', 'add')->middleware('can:edit_order_settings');
            });

            Route::controller(OrderNotificationController::class)
            ->prefix('order_delay_notification')->group(function(){
                Route::get('/', 'view')->middleware('can:view_order_delay');
                Route::post('/add', 'create')->middleware('can:add_order_delay');
                Route::put('/update/{id}', 'modify')->middleware('can:edit_order_delay');
                Route::delete('/delete/{id}', 'delete')->middleware('can:delete_order_delay');
            });
        });
        
        Route::controller(SettingController::class)
        ->group(function(){
            Route::get('/view_time_cancel', 'view_time_cancel_order')->middleware('can:view_time_cancel');
            Route::post('/update_time_cancel', 'update_time_cancel_order')->middleware('can:edit_time_cancel');
            
            Route::get('/resturant_time', 'resturant_time')->middleware('can:view_resturant_time');
            Route::post('/resturant_time_update', 'resturant_time_update')->middleware('can:edit_resturant_time');
            
            Route::get('/tax_type', 'tax')->middleware('can:view_tax_type');
            Route::post('/tax_update', 'tax_update')->middleware('can:edit_tax_type');
            
            Route::get('/delivery_time', 'delivery_time')->middleware('can:view_delivery_time');
            Route::post('/delivery_time_update', 'delivery_time_update')->middleware('can:edit_delivery_time');
            
            Route::get('/preparing_time', 'preparing_time')->middleware('can:view_preparing_time');
            Route::post('/preparing_time_update', 'preparing_time_update')->middleware('can:edit_preparing_time');
            
            Route::get('/notification_sound', 'notification_sound')->middleware('can:view_notification_sound');
            Route::post('/notification_sound_update', 'notification_sound_update')->middleware('can:edit_notification_sound');

            Route::get('/cancelation_notification', 'cancelation_notification');
            Route::put('/update_cancelation_notification', 'update_cancelation_notification');
            Route::get('/cancelation', 'cancelation');
            Route::put('/cancelation_status/{id}', 'cancelation_status');
        });
    });
});

