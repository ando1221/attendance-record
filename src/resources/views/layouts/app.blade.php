<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', 'Attendance Management')</title>

  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
  <header class="header">
    <div class="header__inner">
      {{-- ログイン中ユーザーを受ける --}}
      @php
      /** @var \App\Models\User|null $authUser */
      $authUser = auth()->user();

      // 一般ユーザーの当日勤怠を取得
      $todayAttendance = null;
      $isFinishedToday = false;

      if ($authUser && !$authUser->isAdmin()) {
      $todayAttendance = \App\Models\Attendance::with('status')
      ->where('user_id', $authUser->id)
      ->whereDate('work_date', today())
      ->first();

      $isFinishedToday = optional(optional($todayAttendance)->status)->code === 'finished';
      }
      @endphp

      {{-- ロゴ --}}
      <a class="brand"
        href="{{ auth()->check() && $authUser && $authUser->isAdmin()
            ? route('admin.staff.list')
            : route('attendance.show') }}">
        <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="Attendance Management">
      </a>

      {{-- ログイン・会員登録画面以外でヘッダー機能を表示 --}}
      @unless (Route::is('login', 'register', 'admin.login'))
      <nav class="nav" aria-label="グローバルナビゲーション">
        @auth
        <ul class="nav__list">
          {{-- 管理者メニュー --}}
          @if ($authUser && $authUser->isAdmin())
          <li class="nav__item">
            <a class="nav__link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
          </li>
          <li class="nav__item">
            <a class="nav__link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
          </li>
          <li class="nav__item">
            <a class="nav__link" href="{{ route('admin.stamp_correction_request.list') }}">申請一覧</a>
          </li>
          <li class="nav__item">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <input type="hidden" name="redirect_to" value="admin_login">
              <button class="nav__link nav__logout" type="submit">ログアウト</button>
            </form>
          </li>

          {{-- 一般ユーザーメニュー --}}
          @else
          @if ($isFinishedToday)
          <li class="nav__item">
            <a class="nav__link" href="{{ route('attendance.show') }}">勤怠</a>
          </li>
          <li class="nav__item">
            <a class="nav__link" href="{{ route('attendance.list') }}">今月の出勤一覧</a>
          </li>
          <li class="nav__item">
            <a class="nav__link" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
          </li>
          <li class="nav__item">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <input type="hidden" name="redirect_to" value="user_login">
              <button class="nav__link nav__logout" type="submit">ログアウト</button>
            </form>
          </li>
          @else
          <li class="nav__item">
            <a class="nav__link" href="{{ route('attendance.show') }}">勤怠</a>
          </li>
          <li class="nav__item">
            <a class="nav__link" href="{{ route('attendance.list') }}">勤怠一覧</a>
          </li>
          <li class="nav__item">
            <a class="nav__link" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
          </li>
          <li class="nav__item">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <input type="hidden" name="redirect_to" value="user_login">
              <button class="nav__link nav__logout" type="submit">ログアウト</button>
            </form>
          </li>
          @endif
          @endif
        </ul>
        @endauth
      </nav>
      @endunless
    </div>
  </header>

  {{-- 成功メッセージ --}}
  @if (session('success'))
  <p class="flash-message flash-message--success">
    {{ session('success') }}
  </p>
  @endif

  {{-- エラーメッセージ --}}
  @if (session('error'))
  <p class="flash-message flash-message--error">
    {{ session('error') }}
  </p>
  @endif

  <main>
    @yield('content')
  </main>
</body>

</html>