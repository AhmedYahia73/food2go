<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class CityGate
{
    public static function defineGates()
    {
        Gate::define('view_city', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('City') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_city', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('City') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'add'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_city', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('City') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'edit'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_city', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('City') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'delete'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
