@extends('admin.layout')

@section('title', 'Ayarlar')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST" class="max-w-2xl">
    @csrf
    
    <div class="bg-neutral-900 rounded-xl border border-white/5 divide-y divide-white/5">
        <!-- General Settings -->
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Genel Ayarlar</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Site Adı</label>
                    <input type="text" name="settings[site_name]" 
                           value="{{ $settings['general']?->firstWhere('key', 'site_name')?->value ?? 'BannerArchive' }}"
                           class="w-full px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:border-fuchsia-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Site Açıklaması</label>
                    <textarea name="settings[site_description]" rows="2"
                              class="w-full px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:border-fuchsia-500 resize-none">{{ $settings['general']?->firstWhere('key', 'site_description')?->value ?? 'Film ve dizi banner arşivi' }}</textarea>
                </div>
            </div>
        </div>
        
        <!-- API Settings -->
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">API Ayarları</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">TMDB API Key</label>
                    <input type="text" name="settings[tmdb_api_key]" 
                           value="{{ $settings['api']?->firstWhere('key', 'tmdb_api_key')?->value ?? '' }}"
                           class="w-full px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:border-fuchsia-500 font-mono text-sm"
                           placeholder="API anahtarınızı girin...">
                    <p class="mt-1 text-xs text-neutral-500">TMDB'den ücretsiz API anahtarı alabilirsiniz: <a href="https://www.themoviedb.org/settings/api" target="_blank" class="text-fuchsia-400 hover:underline">themoviedb.org</a></p>
                </div>
            </div>
        </div>
        
        <!-- Appearance Settings -->
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Görünüm Ayarları</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Birincil Renk</label>
                    <div class="flex gap-2">
                        <input type="color" name="settings[primary_color]" 
                               value="{{ $settings['appearance']?->firstWhere('key', 'primary_color')?->value ?? '#d946ef' }}"
                               class="w-12 h-10 rounded cursor-pointer bg-transparent border-0">
                        <input type="text" 
                               value="{{ $settings['appearance']?->firstWhere('key', 'primary_color')?->value ?? '#d946ef' }}"
                               class="flex-1 px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg font-mono text-sm" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-6">
        <button type="submit" class="px-6 py-3 bg-fuchsia-600 hover:bg-fuchsia-500 text-white font-semibold rounded-lg transition-colors">
            Ayarları Kaydet
        </button>
    </div>
</form>
@endsection
