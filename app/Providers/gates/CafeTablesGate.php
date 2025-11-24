<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class CafeTablesGate
{
    public static function defineGates()
    {
        Gate::define('view_cafe_tables', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CafeTable') &&
                $admin->user_positions->roles->where('role', 'CafeTable')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_cafe_tables', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CafeTable') &&
                $admin->user_positions->roles->where('role', 'CafeTable')->pluck('action')->intersect(['all', 'add'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_cafe_tables', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CafeTable') &&
                $admin->user_positions->roles->where('role', 'CafeTable')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_cafe_tables', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('CafeTable') &&
                $admin->user_positions->roles->where('role', 'CafeTable')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
