<!doctype html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'COACHTECH')</title>

        {{-- 共通CSS --}}
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/header.css') }}">

        {{-- ページごとのCSS --}}
        @stack('styles')
    </head>
    <body class="gt-body {{ auth()->check() ? 'gt-body--authed' : 'gt-body--guest' }}">

        {{-- 共通ヘッダー --}}
        @include('components.header')

        <main class="gt-container">
            @yield('content')
        </main>

        @stack('scripts')
    </body>
</html>