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

use App\Http\Controllers\api\admin\customer\CustomerController;
use App\Http\Controllers\api\admin\delivery\DeliveryController;
use App\Http\Controllers\api\admin\branch\BranchController;
use App\Http\Controllers\api\admin\admin\AdminController;

use App\Http\Controllers\api\admin\admin_roles\AdminRolesController;

use App\Http\Controllers\api\admin\product\ProductController;
use App\Http\Controllers\api\admin\product\CreateProductController;

use App\Http\Controllers\api\admin\pos\PosOrderController;
use App\Http\Controllers\api\admin\pos\PosSaleController;

use App\Http\Controllers\api\admin\offer_order\OfferOrderController;

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
use App\Http\Controllers\api\admin\settings\business_setup\CompanyController;
use App\Http\Controllers\api\admin\settings\business_setup\MaintenanceController;
use App\Http\Controllers\api\admin\settings\business_setup\MainBranchesController;
use App\Http\Controllers\api\admin\settings\business_setup\TimeSlotController;
use App\Http\Controllers\api\admin\settings\business_setup\CustomerLoginController;
use App\Http\Controllers\api\admin\settings\business_setup\OrderSettingController;

Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function(){
    Route::controller(OrderController::class)->middleware('can:isOrder')
    ->prefix('order')->group(function(){
        Route::get('/', 'orders');
        Route::get('/count', 'count_orders');
        Route::post('/data', 'orders_data');
        Route::post('/notification', 'notification');
        Route::post('/filter', 'order_filter');
        Route::get('/branches', 'branches');
        Route::get('/order/{id}', 'order');
        Route::get('/invoice/{id}', 'invoice');
        Route::put('/status/{id}', 'status');
        Route::post('/delivery', 'delivery');
    });

    Route::controller(HomeController::class)->middleware('can:isHome')
    ->prefix('home')->group(function(){
        Route::get('/', 'home');
    });

    Route::controller(AdminRolesController::class)->middleware('can:isAdminRoles')
    ->prefix('admin_roles')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(TranslationController::class)->middleware('can:isSettings')
    ->prefix('translation')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(BannerController::class)->middleware('can:isBanner')
    ->prefix('banner')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'banner');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(PaymentController::class)->middleware('can:isPayments')
    ->prefix('payment')->group(function(){
        Route::get('/pending', 'pending');
        Route::get('/receipt/{id}', 'receipt');
        Route::get('/history', 'history');
        Route::put('/approve/{id}', 'approve');
        Route::put('/rejected/{id}', 'rejected');
    });

    Route::controller(PointOffersController::class)->middleware('can:isPointOffers')
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
        Route::controller(PosSaleController::class)->group(function(){
            Route::get('/sale', 'sale');
            Route::post('/order_user/add', 'add_order_user');
        });
        Route::controller(PosOrderController::class)->group(function(){
            Route::get('/order', 'pos_orders');
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

