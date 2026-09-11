<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')
    <title>@yield('title', setting('general.site_name', config('endless.brand.name')))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@100;200;300;400;500;600;700;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --scene-image: url('{{ side_image_url() }}');
            --landing-image: url('{{ landing_bg_url() }}');
        }
        @media (max-width: 767px) { :root { --landing-image: url('{{ landing_bg_url(true) }}'); } }
    </style>
    @stack('head')
</head>
<body class="@yield('body-class')">

@if (session('impersonator_id'))
    <div class="impersonate-bar">
        <span>אתם צופים כ{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('dashboard.stop-impersonating') }}">
            @csrf
            <button type="submit">חזרה לניהול</button>
        </form>
    </div>
@endif

@include('partials.header')

<main class="page-body @yield('main-class')">
    @yield('content')
</main>

@include('partials.footer')

@stack('scripts')
</body>
</html>
