<p align="center">
  <img src="public/images/elw.jpg" alt="BannerArchive Logo" width="120" style="border-radius: 24px;">
</p>

<h1 align="center">BannerArchive</h1>

<p align="center">
  <strong>Film & dizi görsel arşivi ve keşif platformu</strong><br>
  <strong>Movie & TV show visual archive and discovery platform</strong>
</p>

<p align="center">
  <a href="https://bannerarchive.elw.com.tr">bannerarchive.elw.com.tr</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white" alt="Livewire 4">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/TMDB-API-01B4E4?logo=themoviedatabase&logoColor=white" alt="TMDB API">
  <img src="https://img.shields.io/badge/Gemini-AI-4285F4?logo=google&logoColor=white" alt="Gemini AI">
</p>

<p align="center">
  <a href="#-türkçe">Türkçe</a> · <a href="#-english">English</a>
</p>

---

## 🇹🇷 Türkçe

### Hakkinda

BannerArchive, film ve dizi dunyasinin gorsellerini kesfetmek, incelemek ve indirmek icin tasarlanmis modern bir web platformudur. TMDB API entegrasyonu ile milyonlarca film ve dizinin banner, afis, logo ve oyuncu fotograflarina aninda erisim saglar.

### Ozellikler

**Kesfet & Ara**
- Trend filmler ve diziler anasayfa sidebar'inda
- Gercek zamanli arama (autocomplete destekli)
- Kategori bazli gozatma: Vizyonda, Populer, En Iyi Puan, Yakinda

**Gorsel Galeri**
- **Bannerlar** - Yuksek cozunurluklu film/dizi backdrop'lari
- **Afisler** - Posterler, farkli dil ve boyutlarda
- **Logolar** - Resmi film/dizi logolari
- **Oyuncular** - Oyuncu kadrosu ve filmografileri
- **Fragmanlar** - YouTube uzerinden video onizleme

**Indirme & Format**
- Tek tek veya toplu secim ile indirme
- **WebP**, **PNG**, **JPG** format secenekleri
- Secilen cozunurluge gore gorseller o boyutta indirilir
- Toplu indirmelerde otomatik ZIP paketleme

**Gorsel Donusturucu**
- JPG ↔ PNG ↔ WebP format donusumu
- Toplu donusum ve ZIP ile indirme

**Izleme Rehberi**
- Abonelik, kiralama ve satin alma platformlari
- Turkiye'deki yayin haklarina gore filtreleme

**Diger**
- Particle animasyonlu arka plan temalari (5 hazir tema)
- Karanlik tema tasarim
- Tam responsive (mobil, tablet, masaustu)
- Redis tabanli onbellekleme

### Kurulum

**Gereksinimler:** PHP >= 8.2, Composer, Node.js & npm, MySQL, Redis

```bash
git clone https://github.com/kullanici/bannerarchive.git
cd bannerarchive

composer install
npm install

cp .env.example .env
php artisan key:generate
```

`.env` dosyasinda asagidaki degerleri ayarla:

```env
DB_DATABASE=bannerarchive
DB_USERNAME=root
DB_PASSWORD=

TMDB_API_KEY=your_tmdb_api_key

CACHE_STORE=redis
SESSION_DRIVER=database
```

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

### API Anahtarlari

| Servis | Nereden Alinir | Zorunlu |
|--------|----------------|---------|
| **TMDB** | [themoviedb.org/settings/api](https://www.themoviedb.org/settings/api) | Evet |
| **Gemini** | [aistudio.google.com/apikey](https://aistudio.google.com/apikey) | Hayir (admin ozellik) |

### Rate Limiting

| Islem | Misafir | Giris Yapmis |
|-------|---------|--------------|
| Arama | 10/dk | 60/dk |
| Galeri | 20/dk | 120/dk |
| Indirme | 30/dk | 200/dk |
| Gorsel Donusum | 10/dk | 30/dk |

### Guvenlik

- IP bazli brute-force korumasi (5 basarisiz deneme → gecici kilit, 10 → kalici engel)
- Hassas API anahtarlari veritabaninda sifrelenmis olarak saklanir
- Oturum yonetimi veritabani tabanli
- Aktivite ve giris gecmisi loglama
- CSRF korumasi
- Rate limiting tum endpoint'lerde

---

## 🇬🇧 English

### About

BannerArchive is a modern web platform designed for discovering, browsing, and downloading movie and TV show visuals. With TMDB API integration, it provides instant access to banners, posters, logos, and cast photos of millions of movies and TV shows.

### Features

**Discover & Search**
- Trending movies and TV shows on homepage sidebar
- Real-time search with autocomplete
- Category browsing: Now Playing, Popular, Top Rated, Upcoming

**Visual Gallery**
- **Banners** - High-resolution movie/TV show backdrops
- **Posters** - Posters in different languages and sizes
- **Logos** - Official movie/TV show logos
- **Cast** - Cast members and filmographies
- **Trailers** - Video previews via YouTube

**Download & Format**
- Single or bulk selection downloads
- **WebP**, **PNG**, **JPG** format options
- Images are downloaded in the selected resolution
- Automatic ZIP packaging for bulk downloads

**Image Converter**
- JPG ↔ PNG ↔ WebP format conversion
- Batch conversion and ZIP download

**Streaming Guide**
- Subscription, rental, and purchase platforms
- Filtered by streaming availability in Turkey

**Other**
- Particle animated background themes (5 presets)
- Dark theme design
- Fully responsive (mobile, tablet, desktop)
- Redis-based caching

### Installation

**Requirements:** PHP >= 8.2, Composer, Node.js & npm, MySQL, Redis

```bash
git clone https://github.com/user/bannerarchive.git
cd bannerarchive

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Set the following values in `.env`:

```env
DB_DATABASE=bannerarchive
DB_USERNAME=root
DB_PASSWORD=

TMDB_API_KEY=your_tmdb_api_key

CACHE_STORE=redis
SESSION_DRIVER=database
```

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

### API Keys

| Service | Where to Get | Required |
|---------|-------------|----------|
| **TMDB** | [themoviedb.org/settings/api](https://www.themoviedb.org/settings/api) | Yes |
| **Gemini** | [aistudio.google.com/apikey](https://aistudio.google.com/apikey) | No (admin feature) |

### Rate Limiting

| Action | Guest | Authenticated |
|--------|-------|---------------|
| Search | 10/min | 60/min |
| Gallery | 20/min | 120/min |
| Download | 30/min | 200/min |
| Image Convert | 10/min | 30/min |

### Security

- IP-based brute-force protection (5 failed attempts → temporary lock, 10 → permanent block)
- Sensitive API keys stored encrypted in database
- Database-based session management
- Activity and login history logging
- CSRF protection
- Rate limiting on all endpoints

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 12, PHP 8.2 |
| **Frontend** | Livewire 4, Volt 1, Tailwind CSS 4, Alpine.js |
| **Database** | MySQL |
| **Cache** | Redis (Predis) |
| **API** | TMDB (movie data), Google Gemini (AI) |
| **Build** | Vite 7 |
| **Packaging** | JSZip (bulk download) |
| **Animation** | tsParticles |

## Project Structure

```
bannerarchive/
├── app/
│   ├── Http/Controllers/
│   │   ├── TMDBController.php        # Movie/TV API operations
│   │   ├── AuthController.php         # Authentication
│   │   └── Admin/AdminController.php  # Admin panel
│   ├── Models/                        # Eloquent models
│   ├── Services/
│   │   ├── QuoteGeneratorService.php  # Gemini AI integration
│   │   └── ImageConverterService.php  # Image format conversion
│   └── Providers/
├── resources/views/
│   ├── home.blade.php                 # Homepage
│   ├── gallery.blade.php             # Gallery detail page
│   └── livewire/                     # Volt components
│       ├── movie-search.blade.php
│       ├── category-browser.blade.php
│       ├── image-converter.blade.php
│       └── admin/                    # Admin panel pages
├── routes/web.php
└── config/services.php
```

## License

This project is proprietary and all rights are reserved.

## Credits

- [TMDB](https://www.themoviedb.org/) - Movie and TV data
- [Google Gemini](https://ai.google.dev/) - AI infrastructure
- [Laravel](https://laravel.com/) - PHP framework

---
