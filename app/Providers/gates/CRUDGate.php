<?php

namespace App\Providers\gates;
use Illuminate\Support\Facades\Gate;

use App\Models\Admin;

class CRUDGate
{
    public static function defineGates()
    {
        $roles = ['save_filter', 'service_fees', 'purchase_recipe', 'material_product',
        'material_categories', 'expenses_category', 'expenses', 'group_product', 'group',
        'discount_code'];
        $actions = ["view", 'status', 'add', 'update', 'delete'];
        foreach ($roles as $item) {
            foreach ($actions as $element) {
                Gate::define($element . '_' . $item, function (Admin $admin) {
                    if (
                        $admin->admin_position == "super_admin" ||
                        ($admin->user_positions &&
                        $admin->user_positions->roles->pluck('role')->contains($item) &&
                        $admin->user_positions->roles->where('role', $item)->pluck('action')->intersect(['all', $element])->isNotEmpty())
                    ) {
                        return true;
                    }
                    return false;
                }); 
            }
        }
    }
}
