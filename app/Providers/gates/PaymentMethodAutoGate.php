<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class PaymentMethodAutoGate
{
    public static function defineGates()
    {
        Gate::define('view_payment_method_auto', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('PaymentMethodAuto') &&
                $admin->user_positions->roles->where('role', 'PaymentMethodAuto')->pluck('action')->intersect(['all', 'view'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_payment_method_auto', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('PaymentMethodAuto') &&
                $admin->user_positions->roles->where('role', 'PaymentMethodAuto')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('status_payment_method_auto', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('PaymentMethodAuto') &&
                $admin->user_positions->roles->where('role', 'PaymentMethodAuto')->pluck('action')->intersect(['all', 'status'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
