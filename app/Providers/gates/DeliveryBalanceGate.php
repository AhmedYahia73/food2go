<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class DeliveryBalanceGate
{
    public static function defineGates()
    {
        Gate::define('delivery_all_orders', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery Balance') &&
                $admin->user_positions->roles->where('role', 'Delivery Balance')->pluck('action')->intersect(['all', 'all_orders'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delivery_current_orders', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery Balance') &&
                $admin->user_positions->roles->where('role', 'Delivery Balance')->pluck('action')->intersect(['all', 'current_orders'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delivery_faild_orders', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery Balance') &&
                $admin->user_positions->roles->where('role', 'Delivery Balance')->pluck('action')->intersect(['all', 'faild_orders'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delivery_confirm_faild_order', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery Balance') &&
                $admin->user_positions->roles->where('role', 'Delivery Balance')->pluck('action')->intersect(['all', 'confirm_faild_order'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delivery_pay_orders', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery Balance') &&
                $admin->user_positions->roles->where('role', 'Delivery Balance')->pluck('action')->intersect(['all', 'pay_orders'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
        Gate::define('orders_delivery', function (Admin $admin) {
            if (
                $admin->admin_position == "super_admin" ||
                ($admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Delivery Balance') &&
                $admin->user_positions->roles->where('role', 'Delivery Balance')->pluck('action')->intersect(['all', 'delivery_for_orders'])->isNotEmpty())
            ) {
                return true;
            }
            return false;
        });
    }
}
