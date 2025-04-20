<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\CashierMan;

class CashierRoles
{
    public static function defineGates()
    {
        Gate::define('view_addons', function (CashierMan $cashier) {
            if ($cashier->modules->intersect(['all', 'view'])->isNotEmpty()) {
                return true;
            }
            return false;
        });
    }
}
