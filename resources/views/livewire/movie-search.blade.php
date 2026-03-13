<?php

/**
 * Livewire MovieSearch Component (Embedded / Gomulu)
 *
 * YENI KAVRAMLAR:
 * ---------------
 * 1. Gomulu (Embedded) Component -> Full-page component'lardan farki:
 *    - Full-page: Volt::route() ile URL'ye baglanir, #[Layout] gerekir
 *    - Embedded: <livewire:movie-search /> ile baska bir Blade'e gomulur
 *    - #[Layout] KULLANILMAZ cunku kendi sayfasi yok, baska sayfanin icinde yasар
 *
 * 2. `wire:model.live.debounce.500ms` -> Kullanici yazmаyi biraktigindan
 *    500ms sonra sunucuya istek atar. Her tusa basista degil!
 *    - wire:model       -> Sadece form submit'te gonderir
 *    - wire:model.live   -> Her degisiklikte aninda gonderir
 *    - wire:model.blur   -> Input'tan cikinca gonderir
 *    - wire:model.live.debounce.500ms -> 500ms bekleyip gonderir (ARAMA ICIN IDEAL)
 *
 * 3. `$dispatch()` -> Tarayici (browser) event'i yayinlar
 *    - Livewire -> JS iletisimi icin kullanilir
 *    - Blade'de: $dispatch('event-adi', { data })
 *    - JS'de dinle: window.addEventListener('event-adi', e => e.detail)
 *
 * 4. `#[On('event-name')]` -> Baska component veya JS'den event dinler
 *    - JS'den tetikle: Livewire.dispatch('event-name', { key: value })
 *    - Component'ta yakalа: #[On('event-name')] public function handler()
 *
 * 5. `wire:loading` -> Sunucu istegi sirasinda gorunurluk kontrolu
 *    - wire:loading          -> Istek varken GOSTER
 *    - wire:loading.remove   -> Istek varken GIZLE
 *    - wire:loading wire:target="search" -> SADECE search degiskenini hedefle
 */

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // ═══ COMPONENT STATE ═══
    // Bu property'ler sunucuda tutulur, UI bunlari yansitir
    public string $search = '';

    public string $filter = 'all'; // all, movie, tv

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    /**
     * updatedSearch() -> Livewire lifecycle hook
     * $search property'si her degistiginde OTOMATIK cagirilir.
     * wire:model.live.debounce.500ms ile birlikte kullaninca:
     * Kullanici yazmayi birakir -> 500ms bekler -> updatedSearch() calisir
     */
    public function updatedSearch(): void
    {
        if (mb_strlen($this->search) < 2) {
            $this->results = [];

            return;
        }

        $this->performSearch();

        // Sonuclari JS'e gonder (image modal icin gerekli)
        // $this->dispatch() browser event'i olusturur, JS dinleyebilir
        $this->dispatch('results-updated', results: $this->results);
    }

    /**
     * Filtre butonu tiklandiginda cagirilir
     * wire:click="setFilter('movie')" seklinde kullanilir
     * Yeni arama YAPMAZ, sadece mevcut sonuclari filtreler
     */
    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * Sidebar'dan film secildiginde cagirilir
     * JS tarafinda: Livewire.dispatch('select-from-sidebar', { movie: {...} })
     * #[On] attribute'u bu event'i otomatik yakalar
     */
    #[On('select-from-sidebar')]
    public function selectFromSidebar(array $movie): void
    {
        $this->results = [$movie];
        $this->search = $movie['title'];
        $this->filter = $movie['raw_type'];

        $this->dispatch('results-updated', results: $this->results);
    }

    /**
     * Filtrelenmis sonuclari dondurur
     * Blade'de $this->getFilteredResults() ile cagrilir
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFilteredResults(): array
    {
        if ($this->filter === 'all') {
            return $this->results;
        }

        return array_values(array_filter(
            $this->results,
            fn (array $item): bool => $item['raw_type'] === $this->filter
        ));
    }

    /**
     * TMDB API'den arama yap
     * config() kullanarak .env'deki API key'i alir (env() KULLANMA!)
     */
    private function performSearch(): void
    {
        $apiKey = config('services.tmdb.api_key');
        $baseUrl = config('services.tmdb.base_url');

        $response = Http::get("{$baseUrl}/search/multi", [
            'api_key' => $apiKey,
            'query' => $this->search,
            'language' => 'tr-TR',
            'include_adult' => false,
        ]);

        if ($response->successful()) {
            $results = $response->json()['results'] ?? [];

            // Backdrop'i olmayan ve kisi olan sonuclari filtrele
            $results = array_filter($results, fn (array $item): bool => ! empty($item['backdrop_path']) && ($item['media_type'] ?? '') !== 'person'
            );

            $this->results = array_values(
                array_map(fn (array $item): array => $this->formatItem($item, $item['media_type']), $results)
            );
        }
    }

    /**
     * TMDB API sonucunu temiz bir formata cevir
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function formatItem(array $item, string $type): array
    {
        $isMovie = $type === 'movie';

        return [
            'id' => $item['id'],
            'title' => $isMovie ? ($item['title'] ?? '') : ($item['name'] ?? ''),
            'overview' => $item['overview'] ?? '',
            'poster_path' => $item['poster_path'] ?? '',
            'backdrop_path' => $item['backdrop_path'] ?? '',
            'vote_average' => $item['vote_average'] ?? 0,
            'release_date' => $isMovie ? ($item['release_date'] ?? null) : ($item['first_air_date'] ?? null),
            'type' => $isMovie ? 'Film' : 'Dizi',
            'raw_type' => $isMovie ? 'movie' : 'tv',
        ];
    }
};
?>

{{-- ═══════════════════════════════════════════════════════════════
     BLADE TEMPLATE
     Livewire component'in HTML kismi. PHP kodu yukarida, HTML asagida.
     Her wire: directive'i sunucuyla iletisim kurar.
     ═══════════════════════════════════════════════════════════════ --}}
<div>
    {{-- ═══ SEARCH BAR ═══
         wire:model.live.debounce.500ms="search"
         → Input'a her yazdiginda 500ms bekler
         → Sonra $search property'sini gunceller
         → updatedSearch() otomatik calisir
         → Sunucuda TMDB API'ye istek atar
         → Sonuclar $results'a yazilir
         → Blade OTOMATIK yeniden renderlanir --}}
    <div class="relative w-full max-w-2xl mx-auto z-20">
        <div class="relative flex items-center bg-black/80 backdrop-blur-sm border border-neutral-800 rounded-full shadow-2xl overflow-hidden transition-all duration-300 focus-within:border-fuchsia-500 focus-within:shadow-[0_0_30px_rgba(217,70,239,0.3)]">
            <div class="pl-6 text-neutral-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text"
                   wire:model.live.debounce.500ms="search"
                   class="w-full bg-transparent text-white p-4 pl-4 focus:outline-none placeholder-neutral-500 text-lg"
                   placeholder="Search movies or TV shows..."
                   autocomplete="off">

            {{-- wire:loading wire:target="search"
                 → SADECE $search degistiginde (arama sirasinda) gosterilir
                 → Diger islemlerde (filtre vs.) gorunmez --}}
            <div wire:loading wire:target="search" class="pr-4">
                <div class="w-5 h-5 border-2 border-neutral-600 border-t-fuchsia-500 rounded-full animate-spin"></div>
            </div>
        </div>
    </div>

    {{-- ═══ FILTER BUTTONS ═══
         wire:click="setFilter('movie')"
         → Butona tiklaninca sunucudaki setFilter() metodu cagrilir
         → $filter property'si guncellenir
         → Blade yeniden renderlanir (filtrelenmis sonuclarla) --}}
    <div class="flex items-center justify-center gap-2 mt-6">
        @foreach (['all' => 'All', 'movie' => 'Movies', 'tv' => 'TV Shows'] as $value => $label)
            <button wire:click="setFilter('{{ $value }}')"
                    class="cursor-pointer px-4 py-1.5 rounded-full text-xs font-bold transition-all
                        {{ $filter === $value
                            ? 'bg-white text-black shadow-[0_0_15px_rgba(255,255,255,0.3)]'
                            : 'bg-neutral-800/80 backdrop-blur-sm text-white hover:bg-neutral-700 border border-white/10' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ═══ CONTENT AREA ═══ --}}
    <div class="w-full max-w-[1920px] mx-auto mt-10">

        {{-- Loading: Arama sirasinda spinner goster --}}
        <div wire:loading wire:target="search" class="py-12 text-center">
            <div class="inline-block w-10 h-10 border-4 border-neutral-800 border-t-fuchsia-500 rounded-full animate-spin"></div>
        </div>

        {{-- Bos durum: Henuz arama yapilmadi --}}
        @if ($search === '')
            <div class="text-center py-20 opacity-30 pointer-events-none">
                <p class="text-neutral-600 text-sm">Search for high-resolution banners</p>
            </div>
        @endif

        {{-- Sonuc bulunamadi --}}
        @if ($search !== '' && empty($this->getFilteredResults()))
            <div wire:loading.remove wire:target="search" class="text-center py-12">
                @if (! empty($results) && $filter !== 'all')
                    <p class="text-neutral-500">Bu kategoride sonuc bulunamadi.</p>
                @else
                    <p class="text-neutral-500">Sonuc bulunamadi.</p>
                @endif
            </div>
        @endif

        {{-- ═══ RESULTS GRID ═══
             Sunucu tarafinda renderlanir (JS ile DOM manipulasyonu YOK!)
             Her kart tiklaninca $dispatch() ile browser event'i yayinlanir
             JS bu event'i yakalar ve image modal'i acar --}}
        <div wire:loading.remove wire:target="search"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">
            @foreach ($this->getFilteredResults() as $movie)
                {{-- wire:key → Livewire'in her karti benzersiz tanimasi icin SART
                     x-data → Alpine.js scope'u (kart icinde dims takibi)
                     x-on:click → Tiklaninca browser event dispatch eder --}}
                <div wire:key="movie-{{ $movie['id'] }}"
                     x-data="{ dims: '...' }"
                     x-on:click="$dispatch('open-image-modal', { movie: {{ Js::from($movie) }} })"
                     class="group relative bg-neutral-900 rounded-xl overflow-hidden cursor-pointer border border-neutral-800 hover:border-fuchsia-500/50 transition-all duration-300 hover:shadow-[0_0_30px_rgba(217,70,239,0.15)] hover:-translate-y-1 flex flex-col">

                    <div class="aspect-video w-full overflow-hidden bg-neutral-950 relative">
                        {{-- x-on:load → Gorsel yuklenince boyutlari al --}}
                        <img src="https://image.tmdb.org/t/p/w780{{ $movie['backdrop_path'] }}"
                             alt="{{ $movie['title'] }}"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                             loading="lazy"
                             x-on:load="dims = $el.naturalWidth + ' x ' + $el.naturalHeight">

                        <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md px-2 py-1 rounded text-[10px] font-bold text-white border border-white/10">
                            {{ $movie['type'] }}
                        </div>
                    </div>

                    <div class="p-4 flex flex-col gap-2">
                        <h3 class="text-white font-bold text-base leading-tight truncate group-hover:text-fuchsia-400 transition-colors">
                            {{ $movie['title'] }}
                        </h3>
                        <div class="flex items-center justify-between text-xs text-neutral-500 mt-auto">
                            {{-- x-text → Alpine ile dinamik metin (gorsel yuklenince boyut gosterir) --}}
                            <span x-text="dims"
                                  x-bind:class="dims !== '...' && 'text-fuchsia-500/80'"
                                  class="font-mono bg-neutral-800 px-1.5 py-0.5 rounded text-[10px]"></span>
                            <span>{{ $movie['release_date'] ? substr($movie['release_date'], 0, 4) : '' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
