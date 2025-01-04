<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;

use App\Models\Admin;
use App\Models\CompanyInfo;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $company = CompanyInfo::orderByDesc('id')
        ->first();
        $timezone = $company->time_zone ?? config('app.timezone');
        Config::set('app.timezone', $timezone);
        date_default_timezone_set($timezone);
        // if roles have home module
        Gate::define('isHome', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Home')){
                return true;
            }
        });

        // if roles have admin module
        Gate::define('isAdmin', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Admin')){
                return true;
            }
        });
        // if roles have addons module
        Gate::define('isAddons', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Addons')){
                return true;
            }
        });
        // if roles have AdminRoles module
        Gate::define('isAdminRoles', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('AdminRoles')){
                return true;
            }
        });
        // if roles have Banner module
        Gate::define('isBanner', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Banner')){
                return true;
            }
        });
        // if roles have Branch module
        Gate::define('isBranch', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Branch')){
                return true;
            }
        });
        // if roles have Category module
        Gate::define('isCategory', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Category')){
                return true;
            }
        });
        // if roles have Coupon module
        Gate::define('isCoupon', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Coupon')){
                return true;
            }
        });
        // if roles have Customer module
        Gate::define('isCustomer', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Customer')){
                return true;
            }
        });
        // if roles have Deal module
        Gate::define('isDeal', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Deal')){
                return true;
            }
        });
        // if roles have DealOrder module
        Gate::define('isDealOrder', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('DealOrder')){
                return true;
            }
        });
        // if roles have Delivery module
        Gate::define('isDelivery', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Delivery')){
                return true;
            }
        });
        // if roles have OfferOrder module
        Gate::define('isOfferOrder', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('OfferOrder')){
                return true;
            }
        });
        // if roles have Order module
        Gate::define('isOrder', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Order')){
                return true;
            }
        });
        // if roles have Payments module
        Gate::define('isPayments', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Payments')){
                return true;
            }
        });
        // if roles have PointOffers module
        Gate::define('isPointOffers', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('PointOffers')){
                return true;
            }
        });
        // if roles have Product module
        Gate::define('isProduct', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Product')){
                return true;
            }
        });
        // if roles have Settings module
        Gate::define('isSettings', function (Admin $user) {
            if($user->user_positions && $user->user_positions->roles->pluck('role')->contains('Settings')){
                return true;
            }
        });
    }
}
