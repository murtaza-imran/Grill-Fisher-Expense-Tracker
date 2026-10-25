{{-- Date filter: quick chips on phones (Today / 7 days / Month / Last month / Custom) + the date boxes.
     $route = page route, $from/$to = current range, $keep = other filters to keep, $button = submit label --}}
@php
  $t = now();
  $ranges = [
    'today' => [$t->toDateString(), $t->toDateString(), 'Today'],
    'week' => [$t->copy()->subDays(6)->toDateString(), $t->toDateString(), '7 days'],
    'month' => [$t->copy()->startOfMonth()->toDateString(), $t->toDateString(), 'This month'],
    'last' => [$t->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $t->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(), 'Last month'],
  ];
  $active = collect($ranges)->search(fn ($r) => $r[0] === $from && $r[1] === $to) ?: 'custom';
  $keep = array_filter($keep ?? [], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="range-chips mobile-only" role="group" aria-label="Date range">
  @foreach ($ranges as $k => [$a, $b, $label])
    <a href="{{ route($route, array_merge($keep, ['from' => $a, 'to' => $b])) }}" class="rchip {{ $active === $k ? 'on' : '' }}">{{ $label }}</a>
  @endforeach
  <button type="button" class="rchip {{ $active === 'custom' ? 'on' : '' }}" data-toggle-dates><x-icon name="calendar" size="14" />Custom</button>
</div>
<div class="date-range {{ $active === 'custom' ? 'open' : '' }}">
  <input type="date" name="from" value="{{ $from }}" aria-label="From date"><span>to</span>
  <input type="date" name="to" value="{{ $to }}" aria-label="To date">
  <button class="btn btn-ghost btn-sm"><x-icon name="filter" size="15" />{{ $button ?? 'Filter' }}</button>
</div>
