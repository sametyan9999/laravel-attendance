{{-- resources/views/components/header.blade.php --}}
<header class="gt-header">
  <div class="gt-header__inner">

    {{-- ロゴ（★テスト対象なので絶対に消さない） --}}
    <a href="{{ url('/') }}" class="gt-logo" aria-label="COACHTECH">
      <img src="{{ asset('images/COACHTECHlogo.png') }}" alt="COACHTECH"
           style="height:28px; display:block;">
    </a>

    @auth
      {{-- ★ 特定ページでナビを非表示にするためのフラグ --}}
      @hasSection('hide-nav')
        {{-- このセクションが定義されているページでは右側ナビを出さない --}}
      @else
        {{-- =============================
             ▼ 管理者ユーザー（Gate: admin）
           ============================= --}}
        @can('admin')
          <nav class="gt-nav" aria-label="グローバル">
            {{-- 勤怠一覧 --}}
            <a href="{{ route('admin.attendance.list') }}" class="gt-nav__link">
              勤怠一覧
            </a>

            {{-- スタッフ一覧 --}}
            <a href="{{ route('admin.staff.index') }}" class="gt-nav__link">
              スタッフ一覧
            </a>

            {{-- 申請一覧 --}}
            <a href="{{ route('admin.request.index') }}" class="gt-nav__link">
              申請一覧
            </a>

            {{-- 共通ログアウト（Fortify の /logout を使用） --}}
            <form method="POST" action="{{ route('logout') }}" class="gt-nav__form">
              @csrf
              <button type="submit" class="gt-nav__link gt-nav__logout">
                ログアウト
              </button>
            </form>
          </nav>

        {{-- =============================
             ▼ 一般ユーザー
           ============================= --}}
        @else
          <nav class="gt-nav" aria-label="グローバル">
            <a href="{{ route('attendance.today') }}" class="gt-nav__link">勤怠</a>
            <a href="{{ route('attendance.list') }}" class="gt-nav__link">勤怠一覧</a>
            <a href="{{ route('request.my_index') }}" class="gt-nav__link">申請</a>

            <form method="POST" action="{{ route('logout') }}" class="gt-nav__form">
              @csrf
              <button type="submit" class="gt-nav__link gt-nav__logout">
                ログアウト
              </button>
            </form>
          </nav>
        @endcan
      @endif
    @endauth

  </div>
</header>