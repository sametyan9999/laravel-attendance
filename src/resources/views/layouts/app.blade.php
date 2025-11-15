{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','COACHTECH')</title>

  {{-- 共通CSS --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('css/header.css') }}"><!-- ★ ヘッダー専用CSS -->

  {{-- ページごとのCSS --}}
  @stack('styles')
</head>
<body class="gt-body {{ auth()->check() ? 'gt-body--authed' : 'gt-body--guest' }}">

  {{-- ★ 共通ヘッダー（管理者／一般で切り替え） --}}
  @include('components.header')

  {{-- コンテンツ --}}
  <main class="gt-container">
    @yield('content')
  </main>

  {{-- ページごとのJS --}}
  @stack('scripts')
</body>
</html>