<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Reverb config written by startup.sh; PHP reads from storage, no env() needed -->
        @php
            $reverbJson = file_exists(storage_path('app/reverb.json'))
                ? (json_decode(file_get_contents(storage_path('app/reverb.json')), true) ?? [])
                : [];
        @endphp
        <script>
            window.ReverbConfig = {
                key:    "{{ $reverbJson['key']    ?? '' }}",
                host:   "{{ $reverbJson['host']   ?? '' }}",
                port:   {{ $reverbJson['port']   ?? 443 }},
                scheme: "{{ $reverbJson['scheme'] ?? 'https' }}"
            };
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
