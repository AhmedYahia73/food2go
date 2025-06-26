<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class TaxTypeGate
{
    public static function defineGates()
    {
        Gate::define('view_tax_type', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('TaxType') &&
                $admin->user_positions->roles->where('role', 'TaxType')->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_tax_type', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('TaxType') &&
                $admin->user_positions->roles->where('role', 'TaxType')->pluck('action')->intersect(['all', 'edit'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        }); 
    }
}
