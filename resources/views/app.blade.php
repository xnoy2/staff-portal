<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Reverb config — getenv() reads OS env directly, unaffected by config:cache -->
        <script>
            window.ReverbConfig = {
                key:    "{{ getenv('REVERB_APP_KEY') ?: '' }}",
                host:   "{{ getenv('REVERB_HOST') ?: '' }}",
                port:   {{ (int)(getenv('REVERB_PORT') ?: 443) }},
                scheme: "{{ getenv('REVERB_SCHEME') ?: 'https' }}"
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
