<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParticleTheme;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * Public API endpoint — frontend particles background config
     */
    public function getActiveThemeConfig(): JsonResponse
    {
        $theme = ParticleTheme::active();

        if (! $theme) {
            return response()->json(['config' => null]);
        }

        return response()->json(['config' => $theme->config]);
    }

    /**
     * Public API endpoint — bat animation durumu
     */
    public function getBatAnimationConfig(): JsonResponse
    {
        return response()->json([
            'enabled' => (bool) Setting::get('bat_animation_enabled', false),
            'bat_count' => (int) Setting::get('bat_animation_count', 5),
            'bat_speed' => (int) Setting::get('bat_animation_speed', 20),
            'bat_scale' => (float) Setting::get('bat_animation_scale', 2.0),
            'flap_speed' => (float) (Setting::get('bat_flap_speed', 0.4) ?? 0.4),
            'outer_color' => Setting::get('bat_outer_color', '#54556b'),
            'inner_color' => Setting::get('bat_inner_color', '#202020'),
        ]);
    }
}
