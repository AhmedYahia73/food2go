<?php

namespace App\Providers\gates;

use Illuminate\Support\Facades\Gate;
use App\Models\Admin;

class ReportGate
{
    /**
     * Check if the admin has permission for any of the given report actions.
     *
     * @param Admin $admin
     * @param array $actions
     * @return bool
     */
    private static function checkPermission(Admin $admin, array $actions = []): bool
    {
        if ($admin->admin_position === "super_admin") {
            return true;
        }

        if (
            $admin->user_positions &&
            $admin->user_positions->roles->pluck('role')->contains('Reports')
        ) {
            // If no specific actions specified, having any Reports role is enough
            if (empty($actions)) {
                return true;
            }

            $allowedActions = array_merge(['all'], $actions);
            return $admin->user_positions->roles
                ->where('role', 'Reports')
                ->pluck('action')
                ->intersect($allowedActions)
                ->isNotEmpty();
        }

        return false;
    }

    public static function defineGates()
    {
        // General Reports access
        Gate::define('reports', function (Admin $admin) {
            return self::checkPermission($admin);
        });

        // 1. Cashier Report
        $cashierActions = ['Cashier Report', 'cashier report', 'cashier_report', 'Cashier Reports', 'cashier reports', 'cashier_reports'];
        Gate::define('cashier_report', function (Admin $admin) use ($cashierActions) {
            return self::checkPermission($admin, $cashierActions);
        });
        Gate::define('cashier_reports', function (Admin $admin) use ($cashierActions) {
            return self::checkPermission($admin, $cashierActions);
        });

        // 2. Orders Reports
        $ordersActions = ['Orders Reports', 'orders reports', 'orders_reports', 'Orders Report', 'orders report', 'orders_report'];
        Gate::define('orders_reports', function (Admin $admin) use ($ordersActions) {
            return self::checkPermission($admin, $ordersActions);
        });
        Gate::define('orders_report', function (Admin $admin) use ($ordersActions) {
            return self::checkPermission($admin, $ordersActions);
        });

        // 3. Financial Reports
        $financialActions = ['Financial Reports', 'financial reports', 'financial_reports', 'Financial Report', 'financial report', 'financial_report'];
        Gate::define('financial_reports', function (Admin $admin) use ($financialActions) {
            return self::checkPermission($admin, $financialActions);
        });
        Gate::define('financial_report', function (Admin $admin) use ($financialActions) {
            return self::checkPermission($admin, $financialActions);
        });

        // 4. Real Time Sales Reports
        $realTimeActions = ['Real Time Sales Reports', 'real time sales reports', 'real_time_sales_reports', 'Real Time Sales Report', 'real time sales report', 'real_time_sales_report'];
        Gate::define('real_time_sales_reports', function (Admin $admin) use ($realTimeActions) {
            return self::checkPermission($admin, $realTimeActions);
        });
        Gate::define('real_time_sales_report', function (Admin $admin) use ($realTimeActions) {
            return self::checkPermission($admin, $realTimeActions);
        });

        // 5. Product Reports
        $productActions = ['Product Reports', 'product reports', 'product_reports', 'Product Report', 'product report', 'product_report'];
        Gate::define('product_reports', function (Admin $admin) use ($productActions) {
            return self::checkPermission($admin, $productActions);
        });
        Gate::define('product_report', function (Admin $admin) use ($productActions) {
            return self::checkPermission($admin, $productActions);
        });

        // 6. Dine Reports
        $dineActions = ['Dine Reports', 'dine reports', 'dine_reports', 'Dine Report', 'dine report', 'dine_report'];
        Gate::define('dine_reports', function (Admin $admin) use ($dineActions) {
            return self::checkPermission($admin, $dineActions);
        });
        Gate::define('dine_report', function (Admin $admin) use ($dineActions) {
            return self::checkPermission($admin, $dineActions);
        });

        // 7. Invoices Reports
        $invoicesActions = ['Invoices Reports', 'invoices reports', 'invoices_reports', 'Invoices Report', 'invoices report', 'invoices_report'];
        Gate::define('invoices_reports', function (Admin $admin) use ($invoicesActions) {
            return self::checkPermission($admin, $invoicesActions);
        });
        Gate::define('invoices_report', function (Admin $admin) use ($invoicesActions) {
            return self::checkPermission($admin, $invoicesActions);
        });

        // 8. Products Movements
        $movementsActions = [
            'Products Movements', 'products movements', 'products_movements',
            'Product Movements', 'product movements',
            'Products Movement', 'products movement', 'products_movement',
            'Product Movement'
        ];
        Gate::define('products_movements', function (Admin $admin) use ($movementsActions) {
            return self::checkPermission($admin, $movementsActions);
        });
        Gate::define('products_movement', function (Admin $admin) use ($movementsActions) {
            return self::checkPermission($admin, $movementsActions);
        });

        // 9. Hall Reports
        $hallActions = ['Hall Reports', 'hall reports', 'hall_reports', 'Hall Report', 'hall report', 'hall_report'];
        Gate::define('hall_reports', function (Admin $admin) use ($hallActions) {
            return self::checkPermission($admin, $hallActions);
        });
        Gate::define('hall_report', function (Admin $admin) use ($hallActions) {
            return self::checkPermission($admin, $hallActions);
        });

        // 10. Cashier Shortage
        $shortageActions = ['Cashier Shortage', 'cashier shortage', 'cashier_shortage'];
        Gate::define('cashier_shortage', function (Admin $admin) use ($shortageActions) {
            return self::checkPermission($admin, $shortageActions);
        });

        // 11. End Shifts
        $endShiftsActions = ['End Shifts', 'End Shift', 'end shifts', 'end shift', 'end_shifts', 'end_shift'];
        Gate::define('end_shifts', function (Admin $admin) use ($endShiftsActions) {
            return self::checkPermission($admin, $endShiftsActions);
        });
        Gate::define('end_shift', function (Admin $admin) use ($endShiftsActions) {
            return self::checkPermission($admin, $endShiftsActions);
        });

        // Shared dropdown & helper lists gates
        // lists_report is needed by Orders, Financial, Dine, Invoices, Hall reports
        Gate::define('lists_report', function (Admin $admin) {
            return self::checkPermission($admin);
        });

        // branches_list is needed by Real Time Sales report (and any report needing branch lists)
        Gate::define('branches_list', function (Admin $admin) use ($realTimeActions) {
            return self::checkPermission($admin, $realTimeActions) || self::checkPermission($admin);
        });

        // product_report_lists is needed by Product Reports and Products Movements
        Gate::define('product_report_lists', function (Admin $admin) use ($productActions, $movementsActions) {
            $combined = array_merge($productActions, $movementsActions);
            return self::checkPermission($admin, $combined) || self::checkPermission($admin);
        });
    }
}
