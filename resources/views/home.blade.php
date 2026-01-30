@extends('layouts.app')

@section('title', 'BannerArchive')

@section('content')
<div class="min-h-screen bg-neutral-950 text-white flex flex-col md:flex-row font-sans overflow-hidden">

    <!-- Mobile Header -->
    <div class="md:hidden flex items-center justify-between p-4 border-b border-white/5 bg-neutral-900/95 backdrop-blur-md sticky top-0 z-40">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 flex items-center justify-center rounded bg-linear-to-br from-fuchsia-600 to-purple-700 font-bold text-xs">B</div>
            <span class="font-bold tracking-tight">BannerArchive</span>
        </div>
        <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="text-white p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </div>

    <!-- Left Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-80 bg-neutral-900 border-r border-white/5 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 z-50 flex flex-col h-[calc(100vh-65px)] md:h-screen">

        <!-- Sidebar Header -->
        <div class="p-6 pb-4 hidden md:block shrink-0 bg-neutral-900 z-20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-linear-to-br from-fuchsia-600 to-purple-700 shadow-[0_0_15px_rgba(217,70,239,0.3)]">
                    <span class="font-bold text-white">B</span>
                </div>
                <h2 class="text-xl font-bold bg-clip-text text-transparent bg-linear-to-r from-white to-neutral-400 tracking-tight">BannerArchive</h2>
            </div>
        </div>

        <!-- Scrollable Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- POPULAR MOVIES -->
            <div class="flex-1 overflow-hidden border-b border-white/5">
                <div class="px-4 bg-neutral-900 z-20">
                    <h3 class="py-3 text-[10px] uppercase tracking-[0.2em] text-fuchsia-500 font-bold flex items-center gap-2 border-b border-white/5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path></svg>
                        Popüler Filmler
                    </h3>
                </div>
                <div class="sidebar-slider overflow-hidden h-full px-4 scrollbar-hide">
                    <div class="slider-track space-y-2 py-2">
                        @foreach($popularMovies as $item)
                            <div class="group flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 transition-all cursor-pointer">
                                <img src="https://image.tmdb.org/t/p/w92{{ $item['poster_path'] }}" class="w-10 h-14 object-cover rounded shadow-md group-hover:scale-105 transition-transform bg-neutral-800" alt="{{ $item['title'] }}">
                                <div class="min-w-0">
                                    <h4 class="text-sm text-neutral-300 group-hover:text-white truncate">{{ $item['title'] }}</h4>
                                    <span class="text-xs text-neutral-500">{{ $item['release_date'] ? \Carbon\Carbon::parse($item['release_date'])->format('Y') : '' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- POPULAR SHOWS -->
            <div class="flex-1 overflow-hidden">
                <div class="px-4 bg-neutral-900 z-20">
                    <h3 class="py-3 text-[10px] uppercase tracking-[0.2em] text-purple-500 font-bold flex items-center gap-2 border-b border-white/5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Popüler Diziler
                    </h3>
                </div>
                <div class="sidebar-slider overflow-hidden h-full px-4 scrollbar-hide">
                    <div class="slider-track space-y-2 py-2">
                        @foreach($popularShows as $item)
                            <div class="group flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 transition-all cursor-pointer">
                                <img src="https://image.tmdb.org/t/p/w92{{ $item['poster_path'] }}" class="w-10 h-14 object-cover rounded shadow-md group-hover:scale-105 transition-transform bg-neutral-800" alt="{{ $item['title'] }}">
                                <div class="min-w-0">
                                    <h4 class="text-sm text-neutral-300 group-hover:text-white truncate">{{ $item['title'] }}</h4>
                                    <span class="text-xs text-neutral-500">{{ number_format($item['vote_average'], 1) }} Puan</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-[calc(100vh-65px)] md:h-screen overflow-y-auto relative scroll-smooth bg-neutral-950">

        <!-- Particles Background -->
        <div id="tsparticles" class="absolute! inset-0 z-0 pointer-events-auto"></div>

        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-linear-to-b from-transparent via-neutral-950/50 to-neutral-950 pointer-events-none z-1"></div>

        <!-- Hero Section -->
        <div class="relative w-full py-16 px-4 md:px-12 flex flex-col items-center justify-center text-center z-10">
            <h1 class="relative text-4xl md:text-6xl font-black tracking-tighter mb-4">
                <span class="block text-white/90">Search Movies</span>
                <span class="block bg-clip-text text-transparent bg-linear-to-r from-fuchsia-500 via-purple-500 to-cyan-400">& TV Shows</span>
            </h1>
            <p class="text-neutral-400 text-sm md:text-base mb-8">Download high-resolution banners & backdrops</p>

            <!-- Search Bar -->
            <div class="relative w-full max-w-2xl z-20">
                <div class="relative flex items-center bg-black/80 backdrop-blur-sm border border-neutral-800 rounded-full shadow-2xl overflow-hidden transition-all duration-300 focus-within:border-fuchsia-500 focus-within:shadow-[0_0_30px_rgba(217,70,239,0.3)]">
                    <div class="pl-6 text-neutral-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="movieSearch" class="w-full bg-transparent text-white p-4 pl-4 focus:outline-none placeholder-neutral-500 text-lg" placeholder="Search movies or TV shows..." autocomplete="off">
                </div>
            </div>

            <!-- Filters -->
            <div id="filters" class="flex items-center gap-2 mt-6">
                <button class="filter-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-white text-black shadow-[0_0_15px_rgba(255,255,255,0.3)]" data-filter="all">All</button>
                <button class="filter-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-neutral-800/80 backdrop-blur-sm text-white hover:bg-neutral-700 border border-white/10" data-filter="movie">Movies</button>
                <button class="filter-btn px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-neutral-800/80 backdrop-blur-sm text-white hover:bg-neutral-700 border border-white/10" data-filter="tv">TV Shows</button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="relative flex-1 w-full max-w-[1920px] mx-auto px-4 md:px-8 pb-24 z-10">

            <div id="loading" class="hidden py-12 text-center">
                <div class="inline-block w-10 h-10 border-4 border-neutral-800 border-t-fuchsia-500 rounded-full animate-spin"></div>
            </div>

            <div id="initialState" class="text-center py-20 opacity-30 pointer-events-none">
                <p class="text-neutral-600 text-sm">Yüksek çözünürlüklü banner araması yapın</p>
            </div>

            <div id="emptyState" class="hidden text-center py-12">
                <p class="text-neutral-500">Sonuç bulunamadı.</p>
            </div>

            <!-- Grid -->
            <div id="moviesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6"></div>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="imageModal" class="fixed inset-0 z-60 hidden">
    <div class="modal-backdrop absolute inset-0 bg-black/95 backdrop-blur-md transition-opacity duration-300 opacity-0"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4 md:p-8 pointer-events-none">
        <div id="modalContainer" class="w-full max-w-6xl bg-neutral-900 rounded-2xl overflow-hidden shadow-2xl border border-white/10 flex flex-col max-h-[90vh] pointer-events-auto transform transition-all duration-300 scale-95 opacity-0">
            <div id="modalContent"></div>
        </div>
    </div>
</div>
@endsection
