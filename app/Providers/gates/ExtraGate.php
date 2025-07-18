<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class ExtraGate
{
    public static function defineGates()
    {
        Gate::define('view_extra', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Extra') &&
                $admin->user_positions->roles->where('role', 'Extra')->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_extra', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Extra') &&
                $admin->user_positions->roles->where('role', 'Extra')->pluck('action')->intersect(['all', 'add'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_extra', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Extra') &&
                $admin->user_positions->roles->where('role', 'Extra')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_extra', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Extra') &&
                $admin->user_positions->roles->where('role', 'Extra')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
