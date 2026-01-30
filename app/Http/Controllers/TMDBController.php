<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TMDBController extends Controller
{
    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key') ?? env('TMDB_API_KEY');
    }

    public function index()
    {
        // Popüler Filmler (İlk 10)
        $popularMovies = Http::get("{$this->baseUrl}/movie/popular", [
            'api_key' => $this->apiKey,
            'language' => 'tr-TR',
            'page' => 1
        ])->json()['results'] ?? [];

        // Popüler Diziler (İlk 10)
        $popularShows = Http::get("{$this->baseUrl}/tv/popular", [
            'api_key' => $this->apiKey,
            'language' => 'tr-TR',
            'page' => 1
        ])->json()['results'] ?? [];

        // Verileri işle (map)
        $popularMovies = collect($popularMovies)->take(10)->map(fn($m) => $this->formatItem($m, 'movie'));
        $popularShows = collect($popularShows)->take(10)->map(fn($s) => $this->formatItem($s, 'tv'));

        return view('welcome', compact('popularMovies', 'popularShows'));
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['results' => []]);
        }

        // Multi Search (Hem Film Hem Dizi)
        $response = Http::get("{$this->baseUrl}/search/multi", [
            'api_key' => $this->apiKey,
            'query' => $query,
            'language' => 'tr-TR',
            'include_adult' => false,
        ]);

        if ($response->successful()) {
            $results = $response->json()['results'];
            
            // Sadece görseli olanları ve kişi (person) olmayanları filtrele
            $results = array_filter($results, function($item) {
                return !empty($item['backdrop_path']) && $item['media_type'] !== 'person';
            });

            // Formatla
            $results = array_map(fn($item) => $this->formatItem($item, $item['media_type']), $results);

            return response()->json([
                'results' => array_values($results)
            ]);
        }

        return response()->json(['error' => 'TMDB API Hatası'], 500);
    }

    private function formatItem($item, $type)
    {
        $isMovie = $type === 'movie';
        return [
            'id' => $item['id'],
            'title' => $isMovie ? ($item['title'] ?? '') : ($item['name'] ?? ''),
            'poster_path' => $item['poster_path'],
            'backdrop_path' => $item['backdrop_path'],
            'vote_average' => $item['vote_average'] ?? 0,
            'release_date' => $isMovie ? ($item['release_date'] ?? null) : ($item['first_air_date'] ?? null),
            'type' => $isMovie ? 'Film' : 'Dizi',
            'raw_type' => $isMovie ? 'movie' : 'tv'
        ];
    }
}
