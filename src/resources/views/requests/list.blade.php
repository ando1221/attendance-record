@extends('layouts.app')

@section('title', $title)

@section('content')
<main class="page">
  <section class="attendance-list" aria-label="{{ $title }}">
    <header class="attendance-list__header">
      <h1 class="attendance-list__title">{{ $title }}</h1>

      <nav class="request-list-tabs" aria-label="申請状態の切り替え">
        <a
          class="request-list-tabs__link {{ $status === 'pending' ? 'request-list-tabs__link--active' : '' }}"
          href="{{ route($listRouteName, array_merge($routeParams ?? [], ['status' => 'pending'])) }}">
          承認待ち
        </a>

        <a
          class="request-list-tabs__link {{ $status === 'approved' ? 'request-list-tabs__link--active' : '' }}"
          href="{{ route($listRouteName, array_merge($routeParams ?? [], ['status' => 'approved'])) }}">
          承認済み
        </a>
      </nav>
    </header>

    <table class="attendance-table">
      <thead>
        <tr>
          <th>状態</th>
          <th>名前</th>
          <th>対象日時</th>
          <th>申請理由</th>
          <th>申請日時</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($requests as $requestItem)
        @php
        $targetDate = \Carbon\Carbon::parse($requestItem->attendance->work_date);
        $requestedAt = \Carbon\Carbon::parse($requestItem->created_at);
        $statusLabel = optional($requestItem->status)->code === 'approved' ? '承認済み' : '承認待ち';
        @endphp

        <tr>
          <td>{{ $statusLabel }}</td>
          <td>{{ optional($requestItem->user)->name }}</td>
          <td>{{ $targetDate->format('Y/m/d') }}</td>
          <td>{{ $requestItem->requested_note }}</td>
          <td>{{ $requestedAt->format('Y/m/d') }}</td>
          <td>
            <a
              class="attendance-table__detail-link"
              href="{{ route($detailRouteName, ['attendance_correction_request_id' => $requestItem->id]) }}">
              詳細
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="attendance-table__empty">申請データがありません。</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </section>
</main>
@endsection