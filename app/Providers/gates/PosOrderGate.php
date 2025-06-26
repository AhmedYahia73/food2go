<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class PosOrderGate
{
    public static function defineGates()
    {
        Gate::define('view_pos_order', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('PosOrder') &&
                $admin->user_positions->roles->where('role', 'PosOrder')->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('status_pos_table', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('PosTable') &&
                $admin->user_positions->roles->where('role', 'PosOrder')->pluck('action')->intersect(['all', 'status'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
