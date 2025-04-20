<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class ResturantTimeGate
{
    public static function defineGates()
    {
        Gate::define('view_resturant_time', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('ResturantTime') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_resturant_time', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('ResturantTime') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'edit'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        }); 
    }
}
