@extends('layouts.app')

@section('title', '勤怠')

@section('content')
@php
$statusCode = optional(optional($attendance)->status)->code;
@endphp

<section class="page">
  <section class="attendance" aria-label="勤怠情報">
    {{-- 現在の勤怠状態表示 --}}
    <p class="attendance-status">
      @if ($attendance && $statusCode === 'working')
      出勤中
      @elseif ($attendance && $statusCode === 'on_break')
      休憩中
      @elseif ($attendance && $statusCode === 'finished')
      退勤済
      @else
      勤務外
      @endif
    </p>

    {{-- 現在日時表示 --}}
    <section class="attendance-datetime" aria-label="現在日時">
      <time
        class="attendance-date"
        id="current-date"
        datetime="{{ now()->toDateString() }}">
        {{ now()->format('Y年n月j日') }}（{{ ['日', '月', '火', '水', '木', '金', '土'][now()->dayOfWeek] }}）
      </time>

      <time
        class="attendance-time"
        id="current-time"
        datetime="{{ now()->format('H:i:s') }}">
        {{ now()->format('H:i') }}
      </time>
    </section>

    {{-- 勤怠操作ボタン --}}
    <ul class="attendance-actions" aria-label="勤怠操作">
      {{-- 勤務外 --}}
      @if (!$attendance || $statusCode === 'off_duty')
      <li class="attendance-actions__item">
        <form action="{{ route('attendance.clock_in') }}" method="POST">
          @csrf
          <button type="submit" class="action-button" onclick="this.disabled=true; this.form.submit();">
            出勤
          </button>
        </form>
      </li>

      {{-- 勤務中 --}}
      @elseif ($statusCode === 'working')
      <li class="attendance-actions__item">
        <form action="{{ route('attendance.clock_out') }}" method="POST">
          @csrf
          <button type="submit" class="action-button" onclick="this.disabled=true; this.form.submit();">
            退勤
          </button>
        </form>
      </li>
      <li class="attendance-actions__item">
        <form action="{{ route('attendance.break.start') }}" method="POST">
          @csrf
          <button type="submit" class="action-button action-button--sub" onclick="this.disabled=true; this.form.submit();">
            休憩入
          </button>
        </form>
      </li>

      {{-- 休憩中 --}}
      @elseif ($statusCode === 'on_break')
      <li class="attendance-actions__item">
        <form action="{{ route('attendance.break.end') }}" method="POST">
          @csrf
          <button type="submit" class="action-button action-button--sub" onclick="this.disabled=true; this.form.submit();">
            休憩戻
          </button>
        </form>
      </li>

      {{-- 退勤済 --}}
      @elseif ($statusCode === 'finished')
      <li class="attendance-actions__item">
        <p class="attendance-message">お疲れ様でした。</p>
      </li>
      @endif
    </ul>
  </section>
</section>

<script src="{{ asset('js/attendance-index.js') }}"></script>
@endsection