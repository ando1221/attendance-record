@extends('layouts.app')

@section('title', $title)

@section('content')
<main class="page">
    <section class="attendance-list" aria-label="{{ $title }}">
        <header class="attendance-list__header">
            <h1 class="attendance-list__title">{{ $title }}</h1>

            <div class="attendance-list__month-nav" aria-label="日付移動">
                <a
                    class="attendance-list__month-link attendance-list__month-link--prev"
                    href="{{ route($listRouteName, array_merge($routeParams ?? [], ['date' => \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d')])) }}"
                    aria-label="前日を表示">
                    <img
                        class="attendance-list__month-arrow"
                        src="{{ asset('images/icon-arrow.png') }}"
                        alt="">
                    <span>前日</span>
                </a>

                <x-calendar-picker
                    mode="date"
                    name="date"
                    :value="$date"
                    :action="route($dateSelectRouteName, $routeParams ?? [])"
                    :route-params="$routeParams ?? []" />

                <a
                    class="attendance-list__month-link attendance-list__month-link--next"
                    href="{{ route($listRouteName, array_merge($routeParams ?? [], ['date' => \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d')])) }}"
                    aria-label="翌日を表示">
                    <span>翌日</span>
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
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                @php
                $breaks = collect($attendance->breaks ?? []);

                $totalBreakMinutes = $breaks->sum(function ($break) {
                if (!$break->break_start_at || !$break->break_end_at) {
                return 0;
                }

                return $break->break_start_at->diffInMinutes($break->break_end_at);
                });

                $workMinutes = 0;

                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                $workMinutes = $attendance->clock_in_at->diffInMinutes($attendance->clock_out_at) - $totalBreakMinutes;
                }

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;
                $workHours = floor(max($workMinutes, 0) / 60);
                $workRemainMinutes = max($workMinutes, 0) % 60;
                @endphp

                <tr>
                    <td>{{ optional($attendance->user)->name }}</td>
                    <td>{{ $attendance->clock_in_at ? $attendance->clock_in_at->format('H:i') : '' }}</td>
                    <td>{{ $attendance->clock_out_at ? $attendance->clock_out_at->format('H:i') : '' }}</td>
                    <td>{{ ($attendance->clock_in_at || $attendance->clock_out_at) ? sprintf('%02d:%02d', $breakHours, $breakMinutes) : '' }}</td>
                    <td>{{ $workMinutes > 0 ? sprintf('%02d:%02d', $workHours, $workRemainMinutes) : '' }}</td>
                    <td>
                        <a
                            class="attendance-table__detail-link"
                            href="{{ route($detailRouteName, $attendance->id) }}">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="attendance-table__empty">勤怠データがありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</main>

<script src="{{ asset('js/calendar-picker.js') }}"></script>
@endsection