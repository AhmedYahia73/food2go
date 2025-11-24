<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class DealOrderGate
{
    public static function defineGates()
    {
        Gate::define('view_deal_order', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('DealOrder') &&
                $admin->user_positions->roles->where('role', 'DealOrder')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_deal_order', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('DealOrder') &&
                $admin->user_positions->roles->where('role', 'DealOrder')->pluck('action')->intersect(['all', 'add'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
