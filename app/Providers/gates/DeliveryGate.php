<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class DeliveryGate
{
    public static function defineGates()
    {
        Gate::define('view_delivery', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery') &&
                $admin->user_positions->roles->where('role', 'Delivery')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_delivery', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery') &&
                $admin->user_positions->roles->where('role', 'Delivery')->pluck('action')->intersect(['all', 'add'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_delivery', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery') &&
                $admin->user_positions->roles->where('role', 'Delivery')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_delivery', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery') &&
                $admin->user_positions->roles->where('role', 'Delivery')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
