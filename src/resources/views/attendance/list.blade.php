@extends('layouts.app')

@section('title', $title)

@section('content')
<main class="page">
  <section class="attendance-list" aria-label="{{ $title }}">
    <header class="attendance-list__header">
      <h1 class="attendance-list__title">{{ $title }}</h1>

      <div class="attendance-list__month-nav" aria-label="月移動">
        {{-- 前月 --}}
        <a
          class="attendance-list__month-link attendance-list__month-link--prev"
          href="{{ route($listRouteName, array_merge($routeParams ?? [], ['month' => \Carbon\Carbon::parse($month . '-01')->subMonth()->format('Y-m')])) }}"
          aria-label="前月を表示">
          <img
            class="attendance-list__month-arrow"
            src="{{ asset('images/icon-arrow.png') }}"
            alt="">
          <span>前月</span>
        </a>

        {{-- 自作カレンダーUI --}}
        <x-calendar-picker
          mode="month"
          name="month"
          :value="$month"
          :action="route($monthSelectRouteName, $routeParams ?? [])"
          :route-params="$routeParams ?? []" />

        {{-- 翌月 --}}
        <a
          class="attendance-list__month-link attendance-list__month-link--next"
          href="{{ route($listRouteName, array_merge($routeParams ?? [], ['month' => \Carbon\Carbon::parse($month . '-01')->addMonth()->format('Y-m')])) }}"
          aria-label="翌月を表示">
          <span>翌月</span>
          <img
            class="attendance-list__month-arrow attendance-list__month-arrow--next"
            src="{{ asset('images/icon-arrow.png') }}"
            alt="">
        </a>
      </div>
    </header>

    <table class="attendance-table">
      <thead>
        <tr>
          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($days as $day)
        @php
        $date = $day['date'];
        $attendance = $day['attendance'];

        $totalBreakMinutes = 0;
        $workMinutes = 0;

        $breaks = collect($attendance->breaks ?? []);

        $totalBreakMinutes = $breaks->sum(function ($break) {
        if (!$break->break_start_at || !$break->break_end_at) {
        return 0;
        }

        return $break->break_start_at->diffInMinutes($break->break_end_at);
        });

        if ($attendance->clock_in_at && $attendance->clock_out_at) {
        $workMinutes = $attendance->clock_in_at->diffInMinutes($attendance->clock_out_at) - $totalBreakMinutes;
        }

        $breakHours = floor($totalBreakMinutes / 60);
        $breakMinutes = $totalBreakMinutes % 60;

        $workHours = floor(max($workMinutes, 0) / 60);
        $workRemainMinutes = max($workMinutes, 0) % 60;

        $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekDay = $weekDays[$date->dayOfWeek];

        $dateClass = '';
        if ($date->dayOfWeek === 0) {
        $dateClass = 'attendance-table__date--sun';
        } elseif ($date->dayOfWeek === 6) {
        $dateClass = 'attendance-table__date--sat';
        }
        @endphp

        <tr>
          <td class="{{ $dateClass }}">{{ $date->format('m/d') }}（{{ $weekDay }}）</td>
          <td>{{ $attendance->clock_in_at ? $attendance->clock_in_at->format('H:i') : '' }}</td>
          <td>{{ $attendance->clock_out_at ? $attendance->clock_out_at->format('H:i') : '' }}</td>
          <td>
            {{ ($attendance->clock_in_at || $attendance->clock_out_at)
                  ? sprintf('%02d:%02d', $breakHours, $breakMinutes)
                  : '' }}
          </td>
          <td>{{ $workMinutes > 0 ? sprintf('%02d:%02d', $workHours, $workRemainMinutes) : '' }}</td>
          <td>
            <a
              class="attendance-table__detail-link"
              href="{{ route($detailRouteName, $attendance->id) }}">
              詳細
            </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    @if (($isAdminCsvExport ?? false) && isset($csvRouteName))
    <div class="attendance-list__actions">
      <form method="GET" action="{{ route($csvRouteName, $routeParams ?? []) }}">
        @foreach (($routeParams ?? []) as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <input type="hidden" name="month" value="{{ $month }}">
        <button type="submit" class="common-button common-button--dark">CSV出力</button>
      </form>
    </div>
    @endif
  </section>
</main>

<script src="{{ asset('js/calendar-picker.js') }}"></script>
@endsection