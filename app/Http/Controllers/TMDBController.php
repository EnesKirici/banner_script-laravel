<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateQuotesRequest;
use App\Services\QuoteGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class TMDBController extends Controller
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url');
    }

    public function index(): View
    {
        $apiKey = config('services.tmdb.api_key');

        $popularMovies = Http::get("{$this->baseUrl}/movie/popular", [
            'api_key' => $apiKey,
            'language' => 'tr-TR',
            'page' => 1,
        ])->json()['results'] ?? [];

        $popularShows = Http::get("{$this->baseUrl}/tv/popular", [
            'api_key' => $apiKey,
            'language' => 'tr-TR',
            'page' => 1,
        ])->json()['results'] ?? [];

        $popularMovies = collect($popularMovies)->take(10)->map(fn ($m) => $this->formatItem($m, 'movie'));
        $popularShows = collect($popularShows)->take(10)->map(fn ($s) => $this->formatItem($s, 'tv'));

        return view('home', compact('popularMovies', 'popularShows'));
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');

        if (! $query) {
            return response()->json(['results' => []]);
        }

        $apiKey = config('services.tmdb.api_key');

        $response = Http::get("{$this->baseUrl}/search/multi", [
            'api_key' => $apiKey,
            'query' => $query,
            'language' => 'tr-TR',
            'include_adult' => false,
        ]);

        if ($response->successful()) {
            $results = $response->json()['results'];

            $results = array_filter($results, function ($item) {
                return ! empty($item['backdrop_path']) && $item['media_type'] !== 'person';
            });

            $results = array_map(fn ($item) => $this->formatItem($item, $item['media_type']), $results);

            return response()->json([
                'results' => array_values($results),
            ]);
        }

        return response()->json(['error' => 'TMDB API Hatası'], 500);
    }

    public function images(string $type, int $id): JsonResponse
    {
        if (! in_array($type, ['movie', 'tv'])) {
            return response()->json(['error' => 'Geçersiz tür'], 400);
        }

        $apiKey = config('services.tmdb.api_key');

        $response = Http::get("{$this->baseUrl}/{$type}/{$id}/images", [
            'api_key' => $apiKey,
            'include_image_language' => 'tr,en,null',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return response()->json([
                'backdrops' => $data['backdrops'] ?? [],
                'posters' => $data['posters'] ?? [],
                'logos' => $data['logos'] ?? [],
            ]);
        }

        return response()->json(['error' => 'TMDB API Hatası'], 500);
    }

    public function proxyImage(Request $request): \Illuminate\Http\Response
    {
        $path = $request->input('path');
        $size = $request->input('size', 'original');

        if (! $path || ! preg_match('/^\/[a-zA-Z0-9_]+\.\w+$/', $path)) {
            abort(400, 'Geçersiz dosya yolu');
        }

        $validSizes = ['w45', 'w92', 'w154', 'w185', 'w300', 'w342', 'w500', 'w780', 'w1280', 'original'];
        if (! in_array($size, $validSizes)) {
            abort(400, 'Geçersiz boyut');
        }

        $response = Http::timeout(30)->get("https://image.tmdb.org/t/p/{$size}{$path}");

        if ($response->successful()) {
            return response($response->body())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Cache-Control', 'public, max-age=86400');
        }

        abort(502, 'Görsel alınamadı');
    }

    public function generateQuotes(GenerateQuotesRequest $request, QuoteGeneratorService $service): JsonResponse
    {
        $id = $request->integer('id');
        $type = $request->string('type');
        $title = $request->string('title');
        $overview = $request->string('overview');
        $style = $request->string('style', '');
        $regenerate = $request->boolean('regenerate', false);

        $cacheKey = "quotes_{$type}_{$id}";

        if ($regenerate) {
            Cache::forget($cacheKey);
        }

        $quotes = Cache::get($cacheKey);

        if ($quotes === null) {
            $quotes = $service->generateQuotes((string) $title, (string) $overview, (string) $type, (string) $style);

            if (! empty($quotes)) {
                Cache::put($cacheKey, $quotes, now()->addDays(7));
            }
        }

        if (empty($quotes)) {
            return response()->json([
                'error' => 'Sözler üretilemedi. Lütfen tekrar deneyin.',
                'debug' => $service->getLastError(),
            ], 500);
        }

        return response()->json([
            'quotes' => $quotes,
            'model' => $service->getUsedModel(),
        ]);
    }

    /**
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
            'poster_path' => $item['poster_path'],
            'backdrop_path' => $item['backdrop_path'],
            'vote_average' => $item['vote_average'] ?? 0,
            'release_date' => $isMovie ? ($item['release_date'] ?? null) : ($item['first_air_date'] ?? null),
            'type' => $isMovie ? 'Film' : 'Dizi',
            'raw_type' => $isMovie ? 'movie' : 'tv',
        ];
    }
}
