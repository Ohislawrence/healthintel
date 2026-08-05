<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA Primary Meta --}}
    <meta name="theme-color" content="#0E6B5C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="HealthIntel">
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-TileColor" content="#0E6B5C">
    <meta name="msapplication-config" content="/browserconfig.xml">

    {{-- Apple Touch Icons --}}
    <link rel="apple-touch-icon" href="/logo/healthintel-logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/logo/healthintel-logo.png">
    <link rel="mask-icon" href="/logo/healthintel-logo.png" color="#0E6B5C">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="/logo/healthintel-logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/logo/healthintel-logo.png">

    <title>HealthIntel — Understand Your Health in Plain Language</title>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div id="root"></div>

    {{-- PWA & Push Notification Initialization --}}
    <script>
    (function() {
        // Register Service Worker early, before React hydrates
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', {
                scope: '/',
                updateViaCache: 'none',
            }).catch(function(err) {
                console.warn('[PWA] Early SW registration failed:', err);
            });
        }

        // Listen for beforeinstallprompt for PWA install flow
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            window.__pwaInstallPrompt = e;
        });
    })();
    </script>
</body>
</html>