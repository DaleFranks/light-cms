@props([
    'title',
    'description' => null,
    'keywords' => null,
    'currentPath' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }} | {{ $theme->site_name }}</title>
    @if (filled($description))
        <meta name="description" content="{{ $description }}">
    @endif
    @if (filled($keywords))
        <meta name="keywords" content="{{ $keywords }}">
    @endif

    <meta property="og:title" content="{{ $title }}">
    @if (filled($description))
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:type" content="website">
    @if ($theme->logoUrl())
        <meta property="og:image" content="{{ $theme->logoUrl() }}">
    @endif

    @if ($theme->faviconUrl())
        <link rel="icon" href="{{ $theme->faviconUrl() }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $theme->googleFontsUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    <style>
        :root {
        @foreach ($theme->cssVariables() as $name => $value)
            {{ $name }}: {!! str_replace('<', '', $value) !!};
        @endforeach
        }
        {!! $theme->safeCustomCss() !!}
    </style>
</head>
<body class="{{ $theme->bodyClasses() }}">
    <header class="site-header">
        <div class="header-container">
            <div class="header-logo">
                <a href="{{ route('home') }}">
                    @if ($theme->logoUrl())
                        <img src="{{ $theme->logoUrl() }}" alt="{{ $theme->site_name }}" class="logo-icon">
                    @endif
                    @if ($theme->show_site_name || ! $theme->logoUrl())
                        <span class="logo-text">{{ $theme->site_name }}</span>
                    @endif
                </a>
            </div>

            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="site-nav">
                <span class="hamburger"></span>
            </button>

            <nav class="header-nav" id="site-nav" aria-label="Main menu">
                <x-site.menu :items="$menu" :current-path="$currentPath" />
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="content-container">
            {{ $slot }}
        </div>
    </main>

    <footer class="site-footer theme-{{ $theme->footer_style->value }}">
        <div class="footer-container">
            <div class="module footer-module">
                <p class="footer-copyright">&copy; {{ now()->year }} {{ $theme->site_name }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var header = document.querySelector('.site-header');
            var navToggle = document.querySelector('.nav-toggle');
            var headerNav = document.querySelector('.header-nav');

            function scrollHeader() {
                var scrollValue = window.scrollY || document.documentElement.scrollTop;
                header.classList.toggle('scrolled', scrollValue > 50);
            }

            navToggle.addEventListener('click', function () {
                var isOpen = headerNav.classList.toggle('open');
                navToggle.classList.toggle('open', isOpen);
                navToggle.setAttribute('aria-expanded', isOpen);
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 992) {
                    headerNav.classList.remove('open');
                    navToggle.classList.remove('open');
                    navToggle.setAttribute('aria-expanded', 'false');
                }
            });

            scrollHeader();
            window.addEventListener('scroll', scrollHeader, { passive: true });
        });
    </script>
</body>
</html>
