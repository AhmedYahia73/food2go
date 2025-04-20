<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class NotificationSoundGate
{
    public static function defineGates()
    {
        Gate::define('view_notification_sound', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('NotificationSound') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'view'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        }); 
        Gate::define('edit_notification_sound', function (Admin $admin) {
            if (
                $admin->user_positions &&
                $admin->user_positions->roles->pluck('role')->contains('NotificationSound') &&
                $admin->user_positions->roles->pluck('action')->intersect(['all', 'edit'])->isNotEmpty()
            ) {
                return true;
            }
            return false;
        }); 
    }
}
