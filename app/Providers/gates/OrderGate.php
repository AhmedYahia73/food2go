<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class OrderGate
{
    public static function defineGates()
    {
        Gate::define('view_order', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Order') &&
                $admin->user_positions->roles->where('role', 'Order')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('transfer_branch', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Order') &&
                $admin->user_positions->roles->where('role', 'Order')->pluck('action')->intersect(['all', 'transfer_to_branch'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_order', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Order') &&
                $admin->user_positions->roles->where('role', 'Order')->pluck('action')->intersect(['all', 'status'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('log_order', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Order') &&
                $admin->user_positions->roles->where('role', 'Order')->pluck('action')->intersect(['all', 'log'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
    }
}
