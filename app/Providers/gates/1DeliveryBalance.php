<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class DeliveryBalance
{
    public static function defineGates()
    {
        Gate::define('view_restore', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Restore User') &&
                $admin->user_positions->roles->where('role', 'Restore User')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('restore', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Restore User') &&
                $admin->user_positions->roles->where('role', 'Restore User')->pluck('action')->intersect(['all', 'restore'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
