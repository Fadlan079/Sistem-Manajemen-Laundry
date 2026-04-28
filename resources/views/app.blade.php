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
        <!-- <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer data-cfasync="false"></script> -->

        <meta property="og:title" content="Laundry Express Samarinda | HiWash">
        <meta property="og:description" content="HiWash adalah One-Stop Laundry Express Samarinda. Kami menyediakan layanan laundry yang cepat, bersih, dan bisa dilacak secara real-time.">
        <meta property="og:image" content="{{ asset('logo.png') }}">
        <meta property="og:url" content="https://hiwash.my.id/">
        <meta property="og:type" content="website">

        <meta name="description" content="HiWash adalah layanan Laundry Express Samarinda terbaik. Kami melayani proses cuci cepat, bersih, dan wangi dengan sistem yang terintegrasi.">
        <meta name="keywords" content="Laundry Express Samarinda, One-Stop Laundry Express, laundry samarinda, hiwash, cuci cepat samarinda, laundry terdekat">

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
