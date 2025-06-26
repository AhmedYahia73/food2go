<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class OfferOrderGate
{
    public static function defineGates()
    {
        Gate::define('approve_offer_order', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('OfferOrder') &&
                $admin->user_positions->where('action', '')->roles->pluck('action')->intersect(['all', 'approve_offer'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        });
    }
}
