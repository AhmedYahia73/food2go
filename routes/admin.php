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

use App\Http\Controllers\api\admin\customer\CustomerController;
use App\Http\Controllers\api\admin\delivery\DeliveryController;
use App\Http\Controllers\api\admin\branch\BranchController;
use App\Http\Controllers\api\admin\admin\AdminController;

use App\Http\Controllers\api\admin\product\ProductController;
use App\Http\Controllers\api\admin\product\CreateProductController;

use App\Http\Controllers\api\admin\pos\PosOrderController;
use App\Http\Controllers\api\admin\pos\PosSaleController;

use App\Http\Controllers\api\admin\offer_order\OfferOrderController;

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

Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function(){
    Route::controller(OrderController::class)->prefix('order')->group(function(){
        Route::get('/', 'orders');
        Route::put('/status/{id}', 'status');
        Route::post('/delivery', 'delivery');
    });

    Route::controller(TranslationController::class)->prefix('translation')->group(function(){
        Route::get('/', 'view');
        Route::get('/link', 'link');
        Route::post('/add', 'create');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(BannerController::class)->prefix('banner')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(PointOffersController::class)->prefix('offer')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    // Make Deal Module
    Route::controller(DealOrderController::class)->prefix('dealOrder')->group(function(){
        Route::post('/', 'deal_order');
        Route::post('/add', 'add');
    });

    Route::controller(OfferOrderController::class)->prefix('offerOrder')->group(function(){
        Route::post('/', 'check_order');
        Route::post('/approve_offer', 'approve_offer');
    });

    // Make Deal Module
    Route::controller(DealController::class)->prefix('deal')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(AdminController::class)->prefix('admin')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(BranchController::class)->prefix('branch')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(DeliveryController::class)->prefix('delivery')->group(function(){
        Route::get('/', 'view');
        Route::put('/status/{id}', 'status');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::controller(CustomerController::class)->prefix('customer')->group(function(){
        Route::get('/', 'view');
        Route::post('/add', 'create');
        Route::put('/status/{id}', 'status');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });
    
    Route::prefix('coupon')->group(function(){
        Route::controller(CouponController::class)->group(function(){
            Route::get('/', 'view');
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
    
    Route::prefix('product')->group(function(){
        Route::controller(ProductController::class)->group(function(){
            Route::get('/', 'view');
            Route::get('/reviews', 'reviews');
        });
        Route::controller(CreateProductController::class)->group(function(){
            Route::post('/add', 'create'); 
            Route::post('/update/{id}', 'modify'); 
            Route::delete('/delete/{id}', 'delete'); 
        });
    });
    
    Route::prefix('category')->group(function(){
        Route::controller(CategoryController::class)->group(function(){
            Route::get('/', 'view');
            Route::get('/item/{id}', 'category');
            Route::put('/status/{id}', 'status');
            Route::put('/priority/{id}', 'priority');
        });
        Route::controller(CreateCategoryController::class)->group(function(){
            Route::post('/add', 'create'); 
            Route::post('/update/{id}', 'modify'); 
            Route::delete('/delete/{id}', 'delete'); 
        });
    });

    Route::controller(AddonController::class)->prefix('addons')->group(function(){
        Route::get('/', 'view');
        Route::get('/item/{id}', 'addon');
        Route::post('/add', 'create');
        Route::post('/update/{id}', 'modify');
        Route::delete('/delete/{id}', 'delete');
    });

    Route::prefix('settings')->group(function(){
        Route::controller(ExtraController::class)->prefix('extra')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });

        Route::controller(OrderTypeController::class)->prefix('order_type')->group(function(){
            Route::get('/', 'view');
            Route::post('/update', 'modify');
        });

        Route::controller(ZoneController::class)->prefix('zone')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });

        Route::controller(CityController::class)->prefix('city')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(ExcludeController::class)->prefix('exclude')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(TaxController::class)->prefix('tax')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(DiscountController::class)->prefix('discount')->group(function(){
            Route::get('/', 'view');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(PaymentMethodController::class)->prefix('payment_methods')->group(function(){
            Route::get('/', 'view');
            Route::put('/status/{id}', 'status');
            Route::post('/add', 'create');
            Route::post('/update/{id}', 'modify');
            Route::delete('/delete/{id}', 'delete');
        });
        
        Route::controller(SettingController::class)->group(function(){
            Route::get('/view_time_cancel', 'view_time_cancel_order');
            Route::post('/update_time_cancel', 'update_time_cancel_order');
            
            Route::get('/resturant_time', 'resturant_time');
            Route::post('/resturant_time_update', 'resturant_time_update');
            
            Route::get('/tax', 'tax');
            Route::post('/tax_update', 'tax_update');
        });
    });
});

