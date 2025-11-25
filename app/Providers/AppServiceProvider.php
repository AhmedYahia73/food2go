<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;

use App\Models\EmailIntegration;
use App\Models\CompanyInfo;
use App\Providers\gates\AdminGate;
use App\Providers\gates\HomeGate;
use App\Providers\gates\AddonsGate;
use App\Providers\gates\BannerGate;
use App\Providers\gates\AdminRolesGate;
use App\Providers\gates\CategoryGate;
use App\Providers\gates\CouponGate;
use App\Providers\gates\CustomerGate;
use App\Providers\gates\DeliveryGate;
use App\Providers\gates\ProductGate;
use App\Providers\gates\DealGate;
use App\Providers\gates\CashierGate;
use App\Providers\gates\CashierManGate;
use App\Providers\gates\KitchentGate;
use App\Providers\gates\CaptainOrderGate;
use App\Providers\gates\TranslationGate;
use App\Providers\gates\CafeLocationGate;
use App\Providers\gates\CafeTablesGate;
use App\Providers\gates\PointOffersGate;
use App\Providers\gates\BranchGate;
use App\Providers\gates\PosCustomerGate;
use App\Providers\gates\PosAddressGate;
use App\Providers\gates\ExtraGate;
use App\Providers\gates\ZoneGate;
use App\Providers\gates\CityGate;
use App\Providers\gates\TaxGate;
use App\Providers\gates\DiscountGate;
use App\Providers\gates\PaymentMethodGate;
use App\Providers\gates\FinancialAccountingGate;
use App\Providers\gates\MenueGate;
use App\Providers\gates\PosOrderGate;
use App\Providers\gates\OrderTypeGate;
use App\Providers\gates\PaymentMethodAutoGate;
use App\Providers\gates\CompanyInfoGate;
use App\Providers\gates\MaintenanceGate;
use App\Providers\gates\MainBranchesGate;
use App\Providers\gates\TimeSlotGate;
use App\Providers\gates\CustomerLoginGate;
use App\Providers\gates\OrderSettingGate;
use App\Providers\gates\TimeCancelGate;
use App\Providers\gates\ResturantTimeGate;
use App\Providers\gates\TaxTypeGate;
use App\Providers\gates\DeliveryTimeGate;
use App\Providers\gates\PreparingTimeGate;
use App\Providers\gates\NotificationSoundGate;
use App\Providers\gates\DealOrderGate;
use App\Providers\gates\OfferOrderGate;
use App\Providers\gates\OrderGate;
use App\Providers\gates\PaymentGate;
use App\Providers\gates\PosReportsGate;
use App\Providers\gates\OrderDelayGate;
use App\Providers\gates\DeliveryBalanceGate;
use App\Providers\gates\RestoreGate;
use App\Providers\gates\DueGroupGate;
use App\Providers\gates\CRUDGate;
use App\Providers\gates\PreparationManGate;

use App\Providers\Cashier\CashierRoles;
use Illuminate\Support\Facades\Schema;

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
    Schema::defaultStringLength(191);
        try {
            $company = CompanyInfo::orderByDesc('id')
            ->first() ?? collection([]);
            $timezone = $company->time_zone ?? config('app.timezone');
            if ($timezone != '') { 
                Config::set('app.timezone', $timezone);
                date_default_timezone_set($timezone);
            }
        } catch (\Throwable $th) {
        }
        try {
            $email = EmailIntegration::orderByDesc('id')->first();
            $company = CompanyInfo::orderByDesc('id')->first();

            if ($email) {
                Config::set('mail.mailers.smtp.username', $email->email);
                Config::set('mail.mailers.smtp.password', $email->integration_password);
                Config::set('mail.from.address', $email->email);
            }

            if ($company) {
                Config::set('mail.from.name', $company->name);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        // , , 
        AdminGate::defineGates();
        HomeGate::defineGates();
        AddonsGate::defineGates();
        BannerGate::defineGates();
        AdminRolesGate::defineGates();
        CategoryGate::defineGates();
        CouponGate::defineGates();
        CustomerGate::defineGates();
        DealGate::defineGates();
        DeliveryGate::defineGates();
        ProductGate::defineGates();
        CashierGate::defineGates();
        CashierManGate::defineGates();
        KitchentGate::defineGates();
        CaptainOrderGate::defineGates();
        TranslationGate::defineGates();
        CafeLocationGate::defineGates();
        CafeTablesGate::defineGates();
        PointOffersGate::defineGates();
        BranchGate::defineGates();
        PosCustomerGate::defineGates();
        PosAddressGate::defineGates();
        ExtraGate::defineGates();
        ZoneGate::defineGates();
        CityGate::defineGates();
        TaxGate::defineGates();
        DiscountGate::defineGates();
        PaymentMethodGate::defineGates();
        FinancialAccountingGate::defineGates();
        MenueGate::defineGates();
        OrderDelayGate::defineGates();
          
        // ___________________________________________________________________________
        DealOrderGate::defineGates(); // view, add
        OfferOrderGate::defineGates(); // approve_offer
        PaymentGate::defineGates(); // view, status
        OrderGate::defineGates(); // view, status
        PosReportsGate::defineGates(); // view
        PosOrderGate::defineGates(); // PosOrder => view, PosTable => status
        OrderTypeGate::defineGates(); // view, edit
        PaymentMethodAutoGate::defineGates(); // view, edit, status
        CompanyInfoGate::defineGates(); // view, edit
        MaintenanceGate::defineGates(); // view, add
        MainBranchesGate::defineGates(); // view, edit
        TimeSlotGate::defineGates(); // view, edit
        CustomerLoginGate::defineGates(); // view, edit
        OrderSettingGate::defineGates(); // view, edit

        TimeCancelGate::defineGates(); // view, edit
        ResturantTimeGate::defineGates(); // view, edit
        
        TaxTypeGate::defineGates(); // view, edit 
        DeliveryTimeGate::defineGates(); // view, edit 
        PreparingTimeGate::defineGates(); // view, edit 
        NotificationSoundGate::defineGates(); // view, edit
          
        // ________________________  Cashier  ________________________

        CashierRoles::defineGates();
        RestoreGate::defineGates();
        DeliveryBalanceGate::defineGates();
        DueGroupGate::defineGates();
        PreparationManGate::defineGates();
        CRUDGate::defineGates();
    }
}
