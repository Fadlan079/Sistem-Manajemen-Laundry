<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'Laundry') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="icon" href="/logo.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <meta property="og:title" content="HiWash Laundry">
        <meta property="og:description" content="Solusi laundry modern dengan sistematis, cepat, dan terpercaya!">
        <meta property="og:image" content="{{ asset('logo.png') }}">
        <meta property="og:url" content="https://hiwash.my.id/">
        <meta property="og:type" content="website">

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
