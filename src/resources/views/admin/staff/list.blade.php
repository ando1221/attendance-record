@extends('layouts.app')

@section('title', $title)

@section('content')
<main class="page">
  <section class="attendance-list" aria-label="{{ $title }}">
    <header class="attendance-list__header">
      <h1 class="attendance-list__title">{{ $title }}</h1>
    </header>

    <table class="attendance-table">
      <thead>
        <tr>
          <th>名前</th>
          <th>メールアドレス</th>
          <th>月次勤怠</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($staffs as $staff)
        <tr>
          <td>{{ $staff->name }}</td>
          <td>{{ $staff->email }}</td>
          <td>
            <a
              class="attendance-table__detail-link"
              href="{{ route('admin.attendance.staff', ['id' => $staff->id]) }}">
              詳細
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="3" class="attendance-table__empty">スタッフデータがありません。</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </section>
</main>
@endsection