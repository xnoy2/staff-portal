<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Reverb config — read from config() not env() so it survives config:cache -->
        <script>
            window.ReverbConfig = {
                key:    "{{ config('broadcasting.connections.reverb.key') }}",
                host:   "{{ config('broadcasting.connections.reverb.options.host') }}",
                port:   {{ (int) config('broadcasting.connections.reverb.options.port', 443) }},
                scheme: "{{ config('broadcasting.connections.reverb.options.scheme', 'https') }}"
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
