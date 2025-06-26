<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class PaymentGate
{
    public static function defineGates()
    {
        Gate::define('view_payments', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Payment') &&
                $admin->user_positions->roles->where('role', 'Payment')->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('status_payments', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Payment') &&
                $admin->user_positions->roles->where('role', 'Payment')->pluck('action')->intersect(['all', 'status'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
