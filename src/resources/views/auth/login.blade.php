@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
<main class="auth-page">
  <section class="auth-card" aria-label="ログインフォーム">
    <header class="auth-header">
      <h1 class="auth-title">ログイン</h1>
    </header>

    <form class="auth-form" method="POST" action="{{ route('login') }}" novalidate>
      @csrf

      <div class="auth-field">
        <label class="auth-label" for="email">メールアドレス</label>
        <input
          class="auth-input"
          id="email"
          type="email"
          name="email"
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
          type="password"
          name="password">

        @error('password')
        <p class="auth-error">{{ $message }}</p>
        @enderror
      </div>

      <button class="auth-btn" type="submit">ログイン</button>
    </form>

    <footer class="auth-footer">
      <p class="auth-link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
      </p>
    </footer>
  </section>
</main>
@endsection