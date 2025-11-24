<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class HomeGate
{
    public static function defineGates()
    {
        Gate::define('view_home', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Home') &&
                $admin->user_positions->roles->where('role', 'Home')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
