<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class MenueGate
{
    public static function defineGates()
    {
        Gate::define('view_menue', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Menue') &&
                $admin->user_positions->roles->where('role', 'Menue')->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_menue', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Menue') &&
                $admin->user_positions->roles->where('role', 'Menue')->pluck('action')->intersect(['all', 'add'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('status_menue', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Menue') &&
                $admin->user_positions->roles->where('role', 'Menue')->pluck('action')->intersect(['all', 'status'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_menue', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Menue') &&
                $admin->user_positions->roles->where('role', 'Menue')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
