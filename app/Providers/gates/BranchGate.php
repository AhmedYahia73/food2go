<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class BranchGate
{
    public static function defineGates()
    {
        Gate::define('view_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('add_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'add'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('edit_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('delete_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'delete'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        
        Gate::define('product_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'product'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('category_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'category'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
        Gate::define('option_branch', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('Branch') &&
                $admin->user_positions->roles->where('role', 'Branch')->pluck('action')->intersect(['all', 'option'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
