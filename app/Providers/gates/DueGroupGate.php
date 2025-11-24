<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class DueGroupGate
{
    public static function defineGates()
    {
        Gate::define('due_module', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Due Module') &&
                $admin->user_positions->roles->where('role', 'Due Module')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('due_module_payment', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Due Module') &&
                $admin->user_positions->roles->where('role', 'Due Module')->pluck('action')->intersect(['all', 'payment'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
    }
}
