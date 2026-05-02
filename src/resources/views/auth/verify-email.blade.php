@extends('layouts.app')

@section('title', 'メール認証')

@section('content')
<main class="verify-page">
  <section class="verify-box" aria-label="メール認証案内">
    <p class="verify-text">
      登録していただいたメールアドレスに確認メールを送信しました。<br>
      メール認証を完了してください。
    </p>

    <p class="verify-mailhog">
      <a
        href="http://localhost:8025"
        target="_blank"
        rel="noopener noreferrer"
        class="verify-btn">
        認証はこちらから
      </a>
    </p>

    @if (session('status') == 'verification-link-sent')
    <p class="verify-success">確認メールを再送しました。</p>
    @endif

    <footer class="verify-footer">
      <form method="POST" action="{{ route('verification.send') }}" class="verify-resend-form">
        @csrf
        <button type="submit" class="verify-resend-link">
          確認メールを再送する
        </button>
      </form>
    </footer>
  </section>
</main>
@endsection