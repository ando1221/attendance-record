<form
    class="calendar-picker"
    method="GET"
    action="{{ $action }}"
    data-calendar-picker
    data-calendar-mode="{{ $mode }}"
    data-calendar-name="{{ $name }}"
    data-calendar-value="{{ $value }}">
    @foreach (($routeParams ?? []) as $key => $paramValue)
    @if ($key !== $name)
    <input type="hidden" name="{{ $key }}" value="{{ $paramValue }}">
    @endif
    @endforeach

    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-calendar-hidden>

    <button
        class="calendar-picker__trigger"
        type="button"
        data-calendar-trigger>
        <img
            class="calendar-picker__icon"
            src="{{ asset('images/icon-calendar.png') }}"
            alt="">
        <span class="calendar-picker__label" data-calendar-label>
            {{ $mode === 'month'
          ? \Carbon\Carbon::parse($value . '-01')->format('Y/m')
          : \Carbon\Carbon::parse($value)->format('Y/m/d') }}
        </span>
    </button>

    <div class="calendar-picker__panel" hidden data-calendar-panel>
        <div class="calendar-picker__header">
            <button type="button" class="calendar-picker__nav" data-calendar-prev>‹</button>
            <div class="calendar-picker__current" data-calendar-current></div>
            <button type="button" class="calendar-picker__nav" data-calendar-next>›</button>
        </div>

        <div class="calendar-picker__body" data-calendar-body></div>
    </div>
</form>