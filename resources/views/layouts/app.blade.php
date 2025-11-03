<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Banner Downloader - Modern Film Banner Arayıcı')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/themes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Logout Button Component -->
    <x-logout-button />

    <!-- Snow Effect Container -->
    <div id="snowContainer" class="snow-container"></div>
    
    <!-- Settings Button Component -->
    <x-settings-button />

    <!-- Settings Modal Component -->
    <x-settings-modal />

    <!-- Left Sidebar Component -->
    <x-sidebar />

    <div class="container">
        <!-- Main Content -->
        @yield('content')
    </div>

    <!-- Scripts -->
    <script>
        // Laravel CSRF Token ve API URL'i global olarak tanımla
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            apiUrl: '{{ url('/api') }}'
        };
    </script>
    
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>