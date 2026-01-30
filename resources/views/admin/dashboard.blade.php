@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stats Cards -->
    <div class="bg-neutral-900 rounded-xl border border-white/5 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-fuchsia-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold">{{ $stats['total_logins'] }}</p>
                <p class="text-sm text-neutral-500">Toplam Giriş</p>
            </div>
        </div>
    </div>
    
    <div class="bg-neutral-900 rounded-xl border border-white/5 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-purple-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold">{{ $stats['active_theme'] }}</p>
                <p class="text-sm text-neutral-500">Aktif Tema</p>
            </div>
        </div>
    </div>
    
    <div class="bg-neutral-900 rounded-xl border border-white/5 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold">{{ $stats['total_themes'] }}</p>
                <p class="text-sm text-neutral-500">Toplam Tema</p>
            </div>
        </div>
    </div>
    
    <div class="bg-neutral-900 rounded-xl border border-white/5 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-lg font-bold">{{ $stats['last_login']?->created_at->diffForHumans() ?? 'Yok' }}</p>
                <p class="text-sm text-neutral-500">Son Giriş</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Logins -->
<div class="bg-neutral-900 rounded-xl border border-white/5">
    <div class="p-6 border-b border-white/5">
        <h2 class="text-lg font-semibold">Son Girişler</h2>
    </div>
    <div class="divide-y divide-white/5">
        @forelse($recentLogins as $login)
        <div class="p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center">
                <span class="text-sm font-medium">{{ substr($login->user->name ?? 'U', 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium">{{ $login->user->name ?? 'Unknown' }}</p>
                <p class="text-sm text-neutral-500">{{ $login->ip_address }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-neutral-400">{{ $login->created_at->format('d.m.Y H:i') }}</p>
                <p class="text-xs text-neutral-600">{{ $login->created_at->diffForHumans() }}</p>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-neutral-500">
            Henüz giriş kaydı yok.
        </div>
        @endforelse
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="{{ route('admin.particles') }}" class="block bg-neutral-900 rounded-xl border border-white/5 p-6 hover:border-fuchsia-500/50 transition-colors group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-fuchsia-500/10 flex items-center justify-center group-hover:bg-fuchsia-500/20 transition-colors">
                <svg class="w-6 h-6 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold group-hover:text-fuchsia-400 transition-colors">Particles Yönetimi</h3>
                <p class="text-sm text-neutral-500">Tema ve efektleri düzenle</p>
            </div>
        </div>
    </a>
    
    <a href="{{ route('home') }}" target="_blank" class="block bg-neutral-900 rounded-xl border border-white/5 p-6 hover:border-purple-500/50 transition-colors group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-500/20 transition-colors">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold group-hover:text-purple-400 transition-colors">Siteyi Görüntüle</h3>
                <p class="text-sm text-neutral-500">Yeni sekmede aç</p>
            </div>
        </div>
    </a>
</div>
@endsection
