@extends('layouts.app')

@section('title', '勤怠詳細')

@section('content')
<main class="page page--attendance-detail">
  <section class="attendance-detail" aria-label="勤怠詳細">
    <header class="attendance-detail__header">
      <h1 class="attendance-detail__title">勤怠詳細</h1>
    </header>

    @php
    $isAdminApprove = $isAdminApprove ?? false;
    $isAdminEdit = $isAdminEdit ?? false;
    $isRequestDetail = $isRequestDetail ?? false;

    $baseAttendance = ($isRequestDetail || $isAdminApprove) ? $requestData->attendance : $attendance;

    $pendingRequest = null;

    if (!$isAdminApprove && !$isAdminEdit && !$isRequestDetail) {
    $pendingRequest = $baseAttendance->attendanceCorrectionRequests->first(function ($request) {
    return optional($request->status)->code === 'pending';
    });
    }

    $requestDetailStatusCode = null;

    if ($isRequestDetail) {
    $requestDetailStatusCode = optional($requestData->status)->code;
    }

    $hasActualAttendance = false;

    if ($baseAttendance) {
    $hasActualAttendance =
    !is_null($baseAttendance->clock_in_at) ||
    !is_null($baseAttendance->clock_out_at) ||
    collect($baseAttendance->breaks ?? [])->isNotEmpty();
    }

    if ($isAdminApprove || $isRequestDetail) {
    $displayUser = $requestData->user;
    $displayClockIn = optional($requestData->requested_clock_in_at)->format('H:i');
    $displayClockOut = optional($requestData->requested_clock_out_at)->format('H:i');
    $displayNote = $requestData->requested_note ?? '';
    $displayBreaks = collect($requestData->breaks ?? []);
    } else {
    $displayUser = $baseAttendance->user;

    $displayClockIn = $pendingRequest
    ? optional($pendingRequest->requested_clock_in_at)->format('H:i')
    : optional($baseAttendance->clock_in_at)->format('H:i');

    $displayClockOut = $pendingRequest
    ? optional($pendingRequest->requested_clock_out_at)->format('H:i')
    : optional($baseAttendance->clock_out_at)->format('H:i');

    $displayNote = $pendingRequest
    ? ($pendingRequest->requested_note ?? '')
    : ($baseAttendance->note ?? '');

    $displayBreaks = $pendingRequest
    ? collect($pendingRequest->breaks ?? [])
    : collect($baseAttendance->breaks ?? []);
    }

    $breakInputs = $displayBreaks->map(function ($break) {
    return [
    'break_start_at' => optional($break->break_start_at)->format('H:i') ?? '',
    'break_end_at' => optional($break->break_end_at)->format('H:i') ?? '',
    ];
    })->values()->toArray();

    $breakInputs[] = [
    'break_start_at' => '',
    'break_end_at' => '',
    ];

    $clockInValue = old('requested_clock_in_at', $displayClockIn ?? '');
    $clockOutValue = old('requested_clock_out_at', $displayClockOut ?? '');
    $noteValue = old('requested_note', $displayNote ?? '');

    $workDate = \Carbon\Carbon::parse($baseAttendance->work_date);

    $canSubmitEdit = $isAdminEdit || $hasActualAttendance;

    if ($isAdminApprove) {
    $formAction = route('admin.stamp_correction_request.approve', [
    'attendance_correction_request_id' => $requestData->id,
    ]);
    } elseif ($isAdminEdit && $baseAttendance->exists) {
    $formAction = route('admin.attendance.update', ['id' => $baseAttendance->id]);
    } elseif ($isAdminEdit) {
    $formAction = route('admin.attendance.storeByDate', [
    'userId' => $baseAttendance->user_id,
    'date' => $workDate->format('Y-m-d'),
    ]);
    } elseif ($canSubmitEdit) {
    $formAction = route('attendance.correction_request.store', ['attendanceId' => $baseAttendance->id]);
    } else {
    $formAction = '#';
    }

    $isApprovedRequest = $isAdminApprove
    && optional($requestData->status)->code === 'approved';
    @endphp

    <form method="POST" action="{{ $formAction }}">
      @csrf

      <div class="attendance-detail__card">
        <dl class="attendance-detail__list">
          <div class="attendance-detail__row">
            <dt class="attendance-detail__label">名前</dt>
            <dd class="attendance-detail__value">
              {{ $displayUser->name }}
            </dd>
          </div>

          <div class="attendance-detail__row">
            <dt class="attendance-detail__label">日付</dt>
            <dd class="attendance-detail__value attendance-detail__value--date">
              <span class="attendance-detail__date attendance-detail__date--year">
                {{ $workDate->format('Y年') }}
              </span>
              <span class="attendance-detail__date attendance-detail__date--month-day">
                {{ $workDate->format('n月j日') }}
              </span>
            </dd>
          </div>

          <div class="attendance-detail__row">
            <dt class="attendance-detail__label">
              <label for="requested_clock_in_at">出勤・退勤</label>
            </dt>
            <dd class="attendance-detail__value attendance-detail__value--time-range">
              <div class="attendance-detail__field">
                <input
                  id="requested_clock_in_at"
                  class="attendance-detail__input attendance-detail__input--time @error('requested_clock_in_at') attendance-detail__input--error @enderror"
                  type="text"
                  name="requested_clock_in_at"
                  inputmode="numeric"
                  autocomplete="off"
                  value="{{ $clockInValue }}"
                  {{ ($isRequestDetail || $isAdminApprove) ? 'readonly' : '' }}>
                @error('requested_clock_in_at')
                <p class="attendance-detail__error attendance-detail__error--inline">{{ $message }}</p>
                @enderror
              </div>

              <span class="attendance-detail__separator">〜</span>

              <div class="attendance-detail__field">
                <input
                  id="requested_clock_out_at"
                  class="attendance-detail__input attendance-detail__input--time @error('requested_clock_out_at') attendance-detail__input--error @enderror"
                  type="text"
                  name="requested_clock_out_at"
                  inputmode="numeric"
                  autocomplete="off"
                  value="{{ $clockOutValue }}"
                  {{ ($isRequestDetail || $isAdminApprove) ? 'readonly' : '' }}>
                @error('requested_clock_out_at')
                <p class="attendance-detail__error attendance-detail__error--inline">{{ $message }}</p>
                @enderror
              </div>
            </dd>
          </div>

          @foreach ($breakInputs as $index => $breakInput)
          <div class="attendance-detail__row">
            <dt class="attendance-detail__label">
              <label for="breaks_{{ $index }}_start">
                {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
              </label>
            </dt>
            <dd class="attendance-detail__value attendance-detail__value--time-range">
              <div class="attendance-detail__field">
                <input
                  id="breaks_{{ $index }}_start"
                  class="attendance-detail__input attendance-detail__input--time @error(" breaks.$index.break_start_at") attendance-detail__input--error @enderror"
                  type="text"
                  name="breaks[{{ $index }}][break_start_at]"
                  inputmode="numeric"
                  autocomplete="off"
                  value="{{ old("breaks.$index.break_start_at", $breakInput['break_start_at']) }}"
                  {{ ($isRequestDetail || $isAdminApprove) ? 'readonly' : '' }}>
                @error("breaks.$index.break_start_at")
                <p class="attendance-detail__error attendance-detail__error--inline">{{ $message }}</p>
                @enderror
              </div>

              <span class="attendance-detail__separator">〜</span>

              <div class="attendance-detail__field">
                <input
                  id="breaks_{{ $index }}_end"
                  class="attendance-detail__input attendance-detail__input--time @error(" breaks.$index.break_end_at") attendance-detail__input--error @enderror"
                  type="text"
                  name="breaks[{{ $index }}][break_end_at]"
                  inputmode="numeric"
                  autocomplete="off"
                  value="{{ old("breaks.$index.break_end_at", $breakInput['break_end_at']) }}"
                  {{ ($isRequestDetail || $isAdminApprove) ? 'readonly' : '' }}>
                @error("breaks.$index.break_end_at")
                <p class="attendance-detail__error attendance-detail__error--inline">{{ $message }}</p>
                @enderror
              </div>
            </dd>
          </div>
          @endforeach

          <div class="attendance-detail__row attendance-detail__row--textarea">
            <dt class="attendance-detail__label">
              <label for="requested_note">備考</label>
            </dt>
            <dd class="attendance-detail__value attendance-detail__value--note">
              <textarea
                id="requested_note"
                class="attendance-detail__textarea @error('requested_note') attendance-detail__textarea--error @enderror"
                name="requested_note"
                {{ ($isRequestDetail || $isAdminApprove) ? 'readonly' : '' }}>{{ $noteValue }}</textarea>
              @error('requested_note')
              <p class="attendance-detail__error attendance-detail__error--textarea">{{ $message }}</p>
              @enderror
            </dd>
          </div>
        </dl>
      </div>

      <div class="attendance-detail__actions">
        @if ($isRequestDetail && $requestDetailStatusCode === 'pending')
        <p class="attendance-detail__pending">*承認待ちのため修正はできません。</p>
        @elseif ($isRequestDetail && $requestDetailStatusCode === 'approved')
        <p class="attendance-detail__pending">*承認済みの申請です。</p>
        @elseif ($isAdminApprove && $isApprovedRequest)
        <button type="button" class="common-button common-button--dark attendance-detail__button--disabled" disabled>
          承認済み
        </button>
        @elseif ($isAdminApprove)
        <button type="submit" class="common-button common-button--dark">承認</button>
        @elseif (!$isAdminEdit && !$canSubmitEdit)
        <p class="attendance-detail__pending">*勤務外の日は修正申請できません。</p>
        @elseif (!$isAdminEdit && $pendingRequest)
        <p class="attendance-detail__pending">*承認待ちのため修正はできません。</p>
        @else
        <button type="submit" class="common-button common-button--dark">修正</button>
        @endif
      </div>
    </form>
  </section>
</main>
@endsection