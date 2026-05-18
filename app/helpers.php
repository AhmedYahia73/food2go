<?php

use App\Models\Setting;

if (!function_exists('get_tax_setting')) {
    /**
     * Get tax setting with caching - avoids repeated DB queries
     */
    function get_tax_setting(): string
    {
        return \Cache::remember('setting_tax_value', 3600, function () {
            $tax = Setting::where('name', 'tax')->orderByDesc('id')->first();
            if (!$tax) {
                $tax = Setting::create(['name' => 'tax', 'setting' => 'included']);
            }
            return $tax->setting;
        });
    }
}

if (!function_exists('clear_tax_cache')) {
    function clear_tax_cache(): void
    {
        \Cache::forget('setting_tax_value');
        \Cache::forget('setting_tax');
    }
}
