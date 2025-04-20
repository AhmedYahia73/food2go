<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class PosReportsGate
{
    public static function defineGates()
    {
        Gate::define('view_pos_reports', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('PosReports') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
