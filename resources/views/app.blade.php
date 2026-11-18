<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $siteSetting = \App\Models\SiteSetting::current();
            $logoUrl = $siteSetting->logo_path ? \Illuminate\Support\Facades\Storage::url($siteSetting->logo_path) : null;
            $initial = mb_strtoupper(mb_substr(trim((string) ($siteSetting->site_name ?: 'K')), 0, 1)) ?: 'K';
            $svgFavicon = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="16" fill="#2563eb"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="sans-serif" font-weight="bold" font-size="34">' . htmlspecialchars($initial) . '</text></svg>');
        @endphp

        @if($logoUrl)
            <link rel="icon" type="image/png" href="{{ $logoUrl }}">
            <link rel="apple-touch-icon" href="{{ $logoUrl }}">
        @else
            <link rel="icon" type="image/svg+xml" href="{!! $svgFavicon !!}">
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ $siteSetting->site_name ?? config('app.name', 'Kontrakan Pak Dudu') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>