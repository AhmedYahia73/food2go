<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class CustomerLoginGate
{
    public static function defineGates()
    {
        Gate::define('view_customer_login', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CustomerLogin') &&
                $admin->user_positions->roles->where('role', 'CustomerLogin')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_customer_login', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CustomerLogin') &&
                $admin->user_positions->roles->where('role', 'CustomerLogin')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
    }
}
