<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','COACHTECH')</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="gt-body {{ auth()->check() ? 'gt-body--authed' : 'gt-body--guest' }}">
  <header class="gt-header">
    <div class="gt-header__inner">
      {{-- 左：ロゴ（未ログイン時も常に表示。押下でトップ/勤怠へ） --}}
      <a href="{{ auth()->check() ? route('attendance.today') : url('/') }}" class="gt-header__logo" aria-label="COACHTECH">
        <img src="{{ asset('images/COACHTECHlogo.png') }}" alt="COACHTECH">
      </a>

      {{-- 右：ログイン後のみナビを表示 --}}
      @auth
        <nav class="gt-header__nav" aria-label="メインメニュー">
          <a href="{{ route('attendance.today') }}">勤怠</a>
          <a href="{{ route('attendance.list') }}">勤怠一覧</a>
          <a href="{{ route('request.my_index') }}">申請</a>
          <form action="{{ route('logout') }}" method="POST" class="gt-header__logout">
            @csrf
            <button type="submit">ログアウト</button>
          </form>
        </nav>
      @endauth
    </div>
  </header>

  <main class="gt-container">
    @yield('content')
  </main>
</body>
</html>