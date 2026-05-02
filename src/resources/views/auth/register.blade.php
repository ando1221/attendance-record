@extends('layouts.app')

@section('title', '会員登録')

@section('content')
<main class="auth-page">
  <section class="auth-card" aria-label="会員登録フォーム">
    <header class="auth-header">
      <h1 class="auth-title">会員登録</h1>
    </header>

    <form class="auth-form" method="POST" action="{{ route('register') }}" novalidate>
      @csrf

      <div class="auth-field">
        <label class="auth-label" for="name">名前</label>
        <input
          class="auth-input"
          id="name"
          name="name"
          type="text"
          value="{{ old('name') }}">
        @error('name')
        <p class="auth-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="auth-field">
        <label class="auth-label" for="email">メールアドレス</label>
        <input
          class="auth-input"
          id="email"
          name="email"
          type="email"
          value="{{ old('email') }}">
        @error('email')
        <p class="auth-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="auth-field">
        <label class="auth-label" for="password">パスワード</label>
        <input
          class="auth-input"
          id="password"
          name="password"
          type="password">
        @error('password')
        <p class="auth-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="auth-field">
        <label class="auth-label" for="password_confirmation">パスワード確認</label>
        <input
          class="auth-input"
          id="password_confirmation"
          name="password_confirmation"
          type="password">
        @error('password_confirmation')
        <p class="auth-error">{{ $message }}</p>
        @enderror
      </div>

      <button class="auth-btn" type="submit">登録する</button>
    </form>

    <footer class="auth-footer">
      <p class="auth-link">
        <a href="{{ route('login') }}">ログインはこちら</a>
      </p>
    </footer>
  </section>
</main>
@endsection