<?php
namespace App\Helpers;

// Dans app/Helpers/SettingsHelper.php

if (!function_exists('setting')) {
    /**
     * Helper global pour accéder aux paramètres
     */
    function setting($key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (!function_exists('settings')) {
    /**
     * Helper global pour accéder à plusieurs paramètres
     */
    function settings($group = null)
    {
        if ($group) {
            return \App\Models\Setting::getGroup($group);
        }
        
        return \App\Models\Setting::getPublicSettings();
    }
}