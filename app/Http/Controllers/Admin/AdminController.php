<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreThemeRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\LoginHistory;
use App\Models\ParticleTheme;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
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

    public function particles(): View
    {
        $themes = ParticleTheme::orderBy('is_preset', 'desc')->orderBy('name')->get();
        $activeTheme = ParticleTheme::active();

        return view('admin.particles', compact('themes', 'activeTheme'));
    }

    public function activateTheme(ParticleTheme $theme): RedirectResponse
    {
        $theme->activate();

        return back()->with('success', "'{$theme->name}' teması aktifleştirildi.");
    }

    public function storeTheme(StoreThemeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $theme = ParticleTheme::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'config' => json_decode($validated['config'], true),
            'preview_color' => $validated['preview_color'] ?? '#a855f7',
            'is_preset' => false,
            'is_active' => false,
        ]);

        return back()->with('success', "'{$theme->name}' teması oluşturuldu.");
    }

    public function updateTheme(UpdateThemeRequest $request, ParticleTheme $theme): RedirectResponse
    {
        $validated = $request->validated();

        $theme->update([
            'name' => $validated['name'],
            'config' => json_decode($validated['config'], true),
            'preview_color' => $validated['preview_color'] ?? $theme->preview_color,
        ]);

        return back()->with('success', "'{$theme->name}' teması güncellendi.");
    }

    public function destroyTheme(ParticleTheme $theme): RedirectResponse
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

    public function getActiveThemeConfig(): JsonResponse
    {
        $theme = ParticleTheme::active();

        if (! $theme) {
            return response()->json(['config' => null]);
        }

        return response()->json(['config' => $theme->config]);
    }

    public function settings(): View
    {
        $settings = Setting::all()->groupBy('group');

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        foreach ($request->settings as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Ayarlar güncellendi.');
    }

    public function seedPresets(): RedirectResponse
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

        if (! ParticleTheme::active()) {
            ParticleTheme::where('slug', 'hexagons')->first()?->activate();
        }

        return back()->with('success', "{$count} preset tema yüklendi.");
    }
}
