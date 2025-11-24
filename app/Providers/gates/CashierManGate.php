<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class CashierManGate
{
    public static function defineGates()
    {
        Gate::define('view_cashier_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CashierMan') &&
                $admin->user_positions->roles->where('role', 'CashierMan')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_cashier_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CashierMan') &&
                $admin->user_positions->roles->where('role', 'CashierMan')->pluck('action')->intersect(['all', 'add'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_cashier_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CashierMan') &&
                $admin->user_positions->roles->where('role', 'CashierMan')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_cashier_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CashierMan') &&
                $admin->user_positions->roles->where('role', 'CashierMan')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
