<?php

namespace App\Providers\Cashier;
use Illuminate\Support\Facades\Gate;

use App\Models\CashierMan;

class CashierRoles
{
    public static function defineGates()
    {
        Gate::define('take_away', function (CashierMan $cashier) {
            if ($cashier->take_away) {
                return true;
            }
            return false;
        });
        Gate::define('dine_in', function (CashierMan $cashier) {
            if ($cashier->dine_in) {
                return true;
            }
            return false;
        });
        Gate::define('delivery', function (CashierMan $cashier) {
            if ($cashier->delivery) {
                return true;
            }
            return false;
        });
        Gate::define('car_slow', function (CashierMan $cashier) {
            if ($cashier->car_slow) {
                return true;
            }
            return false;
        });
        // ___________________ Reports ________________________
        Gate::define('branch_reports', function (CashierMan $cashier) {
            if ($cashier->roles->pluck('roles')->contains('branch_reports')) {
                return true;
            }
            return false;
        });
        Gate::define('all_reports', function (CashierMan $cashier) {
            if ($cashier->roles->pluck('roles')->contains('all_reports')) {
                return true;
            }
            return false;
        });
        Gate::define('table_status', function (CashierMan $cashier) {
            if ($cashier->roles->pluck('roles')->contains('table_status')) {
                return true;
            }
            return false;
        });
    }
}
