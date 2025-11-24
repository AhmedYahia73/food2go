<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class TimeCancelGate
{
    public static function defineGates()
    {
        Gate::define('view_time_cancel', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('TimeCancel') &&
                $admin->user_positions->roles->where('role', 'TimeCancel')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_time_cancel', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('TimeCancel') &&
                $admin->user_positions->roles->where('role', 'TimeCancel')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
    }
}
