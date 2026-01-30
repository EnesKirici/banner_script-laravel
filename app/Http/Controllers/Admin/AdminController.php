<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\ParticleTheme;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_logins' => LoginHistory::where('success', true)->count(),
            'active_theme' => ParticleTheme::active()?->name ?? 'None',
            'total_themes' => ParticleTheme::count(),
            'last_login' => LoginHistory::where('success', true)->latest()->first(),
        ];

        $recentLogins = LoginHistory::with('user')
            ->where('success', true)
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentLogins'));
    }

    public function particles()
    {
        $themes = ParticleTheme::orderBy('is_preset', 'desc')->orderBy('name')->get();
        $activeTheme = ParticleTheme::active();
        
        return view('admin.particles', compact('themes', 'activeTheme'));
    }

    public function activateTheme(ParticleTheme $theme)
    {
        $theme->activate();
        
        return back()->with('success', "'{$theme->name}' teması aktifleştirildi.");
    }

    public function storeTheme(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'config' => 'required|json',
            'preview_color' => 'nullable|string|max:20',
        ]);

        $theme = ParticleTheme::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'config' => json_decode($request->config, true),
            'preview_color' => $request->preview_color ?? '#a855f7',
            'is_preset' => false,
            'is_active' => false,
        ]);

        return back()->with('success', "'{$theme->name}' teması oluşturuldu.");
    }

    public function updateTheme(Request $request, ParticleTheme $theme)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'config' => 'required|json',
            'preview_color' => 'nullable|string|max:20',
        ]);

        $theme->update([
            'name' => $request->name,
            'config' => json_decode($request->config, true),
            'preview_color' => $request->preview_color ?? $theme->preview_color,
        ]);

        return back()->with('success', "'{$theme->name}' teması güncellendi.");
    }

    public function destroyTheme(ParticleTheme $theme)
    {
        if ($theme->is_preset) {
            return back()->with('error', 'Preset temalar silinemez.');
        }

        if ($theme->is_active) {
            return back()->with('error', 'Aktif tema silinemez. Önce başka bir temayı aktifleştirin.');
        }

        $name = $theme->name;
        $theme->delete();

        return back()->with('success', "'{$name}' teması silindi.");
    }

    public function getActiveThemeConfig()
    {
        $theme = ParticleTheme::active();
        
        if (!$theme) {
            return response()->json(['config' => null]);
        }

        return response()->json(['config' => $theme->config]);
    }

    public function settings()
    {
        $settings = Setting::all()->groupBy('group');
        
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Ayarlar güncellendi.');
    }

    public function seedPresets()
    {
        $presets = ParticleTheme::getDefaultPresets();
        $count = 0;

        foreach ($presets as $preset) {
            ParticleTheme::firstOrCreate(
                ['slug' => $preset['slug']],
                array_merge($preset, ['is_preset' => true])
            );
            $count++;
        }

        // Activate first preset if none active
        if (!ParticleTheme::active()) {
            ParticleTheme::where('slug', 'hexagons')->first()?->activate();
        }

        return back()->with('success', "{$count} preset tema yüklendi.");
    }
}
