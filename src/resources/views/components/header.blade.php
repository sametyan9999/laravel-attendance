<header class="gt-header">
  <div class="gt-header__inner">
    <a href="{{ url('/') }}" class="gt-logo" aria-label="COACHTECH">
      <img src="{{ asset('images/COACHTECHlogo.png') }}" alt="COACHTECH" style="height:28px; display:block;">
      <span class="sr-only">COACHTECH</span>
    </a>

    @auth
      <nav class="gt-nav" aria-label="グローバル">
        <a href="{{ route('attendance.today') }}" class="gt-nav__link">勤怠</a>
        <a href="{{ route('attendance.list') }}" class="gt-nav__link">勤怠一覧</a>
        <a href="{{ route('request.my_index') }}" class="gt-nav__link">申請</a>
        <form method="POST" action="{{ route('logout') }}" class="gt-nav__form">
          @csrf
          <button type="submit" class="gt-nav__link gt-nav__logout">ログアウト</button>
        </form>
      </nav>
    @endauth
  </div>
</header>