<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class PreparationManGate
{
    public static function defineGates()
    {
        Gate::define('view_preparation_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Preparation Man') &&
                $admin->user_positions->roles->where('role', 'Preparation Man')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('status_preparation_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Preparation Man') &&
                $admin->user_positions->roles->where('role', 'Preparation Man')->pluck('action')->intersect(['all', 'status'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('add_preparation_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Preparation Man') &&
                $admin->user_positions->roles->where('role', 'Preparation Man')->pluck('action')->intersect(['all', 'add'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_preparation_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Preparation Man') &&
                $admin->user_positions->roles->where('role', 'Preparation Man')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('delete_preparation_man', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Preparation Man') &&
                $admin->user_positions->roles->where('role', 'Preparation Man')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        }); 
    }
}
