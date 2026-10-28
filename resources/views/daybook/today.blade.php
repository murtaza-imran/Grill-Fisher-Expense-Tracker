@extends('layouts.app')
@section('title', 'Today')
@section('heading', 'Today')

@php
  $rs = fn ($n) => 'Rs. ' . number_format((int) $n);
  $perDay = $target['perDay'];
  $diff = $cash - $perDay;
  $pct = $perDay > 0 ? max(0, min(100, round($cash / $perDay * 100))) : 0;
  $prev = $d->copy()->subDay()->toDateString();
  $next = $isToday ? null : $d->copy()->addDay()->toDateString();
  $label = $isToday ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('l'));
  $icons = ['BBQ' => 'flame', 'Fish' => 'fish', 'Other' => 'receipt'];
@endphp

@section('toolbar')
  <form class="toolbar db-nav" method="GET" action="{{ route('daybook') }}">
    <a href="{{ route('daybook', ['date' => $prev]) }}" class="circle" aria-label="Previous day"><x-icon name="arrow-left" size="17" /></a>
    <div class="db-date"><b>{{ $label }}</b><span>{{ $d->format('j F Y') }}</span></div>
    @if ($next)
      <a href="{{ route('daybook', ['date' => $next]) }}" class="circle" aria-label="Next day"><x-icon name="arrow-left" size="17" style="transform: rotate(180deg)" /></a>
    @else
      <span class="circle db-off" aria-hidden="true"><x-icon name="arrow-left" size="17" style="transform: rotate(180deg)" /></span>
    @endif
    <input type="date" name="date" value="{{ $d->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Pick a day" onchange="this.form.submit()">
    @unless ($isToday)<a href="{{ route('daybook') }}" class="btn btn-ghost btn-sm">Back to today</a>@endunless
  </form>
@endsection

@section('content')
  {{-- Sale − Daily expenses = Day cash, in the dashboard's card style --}}
  <div class="kpis kpis-3 db-kpis">
    <div class="kpi">
      <div class="kpi-top"><small>Sale</small><span class="kpi-ic gold"><x-icon name="trend-up" size="16" /></span></div>
      <strong>{{ $rs($sale) }}</strong>
      <span>Cash {{ $rs($cashSale) }} · Online {{ $rs($onlineSale) }}</span>
    </div>
    <div class="kpi">
      <div class="kpi-top"><small>Daily expenses</small><span class="kpi-ic blue"><x-icon name="wallet" size="16" /></span></div>
      <strong>{{ $rs($dailyExp) }}</strong>
      <span>{{ $daily->count() }} {{ Str::plural('expense', $daily->count()) }} · minus from sale</span>
    </div>
    <div class="kpi db-main">
      <div class="kpi-top"><small>Day cash</small><span class="kpi-ic {{ $cash < 0 ? 'bad' : 'good' }}"><x-icon name="banknote" size="16" /></span></div>
      <strong class="{{ $cash < 0 ? 'neg' : 'pos' }}">{{ $cash < 0 ? '−' : '' }}{{ $rs(abs($cash)) }}</strong>
      <span>Sale − daily expenses</span>
    </div>
    <div class="kpi">
      <div class="kpi-top"><small>Daily target</small><span class="kpi-ic"><x-icon name="calendar" size="16" /></span></div>
      <strong>{{ $perDay ? $rs($perDay) : '—' }}</strong>
      <span>{{ $perDay ? 'Monthly costs ÷ ' . $d->daysInMonth . ' days' : 'Add monthly costs to see it' }}</span>
    </div>
  </div>

  <section class="card db-status {{ $closing ? 'is-closed' : '' }}">
    <div class="db-status-main">
      <div class="db-status-text">
        @if ($closing)
          <span class="db-badge"><x-icon name="check" size="14" />Day closed at {{ $closing->closed_at->format('g:i A') }}</span>
        @endif
        <b>
          @if ($sale === 0) No sale entered yet for {{ strtolower($label) === 'today' ? 'today' : $d->format('j M') }}.
          @elseif ($perDay === 0) Day cash {{ $rs($cash) }}.
          @elseif ($diff >= 0) ✅ Target covered — <span class="pos">+{{ $rs($diff) }}</span> real profit
          @else ⚠️ <span class="neg">{{ $rs(-$diff) }}</span> short of the daily target
          @endif
        </b>
        @if ($perDay > 0)
          <div class="db-bar"><i style="width: {{ $pct }}%" class="{{ $diff >= 0 ? 'ok' : '' }}"></i></div>
          <small>{{ $rs($cash) }} of {{ $rs($perDay) }} target ({{ $pct }}%)</small>
        @endif
      </div>
      <div class="db-status-btns">
        @if ($closing)
          <a class="btn btn-wa btn-sm" href="https://wa.me/?text={{ rawurlencode($whatsapp) }}" target="_blank" rel="noopener"><x-icon name="message" size="15" />WhatsApp summary</a>
          <form method="POST" action="{{ route('daybook.reopen') }}" class="inline">@csrf<input type="hidden" name="date" value="{{ $d->toDateString() }}"><button class="btn btn-ghost btn-sm" type="submit">Open again</button></form>
        @else
          <form method="POST" action="{{ route('daybook.close') }}" data-confirm="Close {{ $isToday ? 'today' : $d->format('D, j M') }}?" data-confirm-text="Day cash {{ $rs($cash) }} (sale {{ $rs($sale) }} − daily expenses {{ $rs($dailyExp) }}). You can open it again later." data-confirm-ok="Close day" data-confirm-kind="neutral">
            @csrf<input type="hidden" name="date" value="{{ $d->toDateString() }}">
            <button class="btn btn-sm" type="submit"><x-icon name="check" size="15" />Close day</button>
          </form>
        @endif
      </div>
    </div>
  </section>

  <div class="grid2 db-cols">
    <section class="card">
      <div class="card-head">
        <div><span class="eyebrow">{{ $sales->count() }} {{ Str::plural('sale', $sales->count()) }} · {{ $rs($sale) }}</span><div class="title">Sales</div></div>
        <a href="{{ route('sales.create', ['date' => $d->toDateString()]) }}" data-modal-form class="btn btn-sm"><x-icon name="plus" size="15" />Add sale</a>
      </div>
      @if ($sale > 0)
        <div class="db-chips">
          @foreach (\App\Models\Sale::CATEGORIES as $c)
            @if (!empty($byCat[$c]))<span class="pill"><x-icon :name="$icons[$c]" size="13" />{{ $c }} {{ $rs($byCat[$c]) }}</span>@endif
          @endforeach
        </div>
      @endif
      <div class="rows">
        @forelse ($sales as $s)
          <a class="row-item" href="{{ route('sales.edit', $s) }}" data-modal-form>
            <span class="av s"><x-icon name="receipt" size="18" /></span>
            <div><b>{{ $s->summary() }}</b><small>{{ $s->created_at?->format('g:i A') }}</small></div>
            <span class="status {{ $s->payment }}">{{ ucfirst($s->payment) }}</span>
            <span class="amt">{{ $rs($s->total) }}</span>
          </a>
        @empty
          <div class="empty-state"><x-icon name="receipt" size="26" /><b>No sales yet</b><span>Add each sale, or one total at night.</span></div>
        @endforelse
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <div><span class="eyebrow">Minus from this day's sale · {{ $rs($dailyExp) }}</span><div class="title">Daily expenses</div></div>
        <a href="{{ route('expenses.create', ['date' => $d->toDateString(), 'kind' => 'daily']) }}" data-modal-form class="btn btn-ghost btn-sm"><x-icon name="plus" size="15" />Add</a>
      </div>
      <div class="rows">
        @forelse ($daily as $e)
          <a class="row-item" href="{{ route('expenses.edit', $e) }}" data-modal-form>
            <span class="av e"><x-icon name="wallet" size="18" /></span>
            <div><b>{{ $e->category }}</b><small>{{ collect([$e->vendor, $e->note])->filter()->implode(' · ') ?: 'Daily expense' }}</small></div>
            <span class="status {{ $e->status === 'pending' ? 'expense' : 'cash' }}">{{ ucfirst($e->status) }}</span>
            <span class="amt">{{ $rs($e->amount) }}</span>
          </a>
        @empty
          <div class="empty-state"><x-icon name="wallet" size="26" /><b>No daily expenses yet</b><span>Gas, ice, packaging, petrol…</span></div>
        @endforelse
      </div>
      @if ($monthlyToday->count())
        <div class="db-monthly-note">
          <x-icon name="calendar" size="15" />
          <span>Monthly costs paid this day (counted in the <a class="link" href="{{ route('daybook.month', ['m' => $d->format('Y-m')]) }}">month</a>, not here):
            @foreach ($monthlyToday as $e)<b>{{ $e->category }} {{ $rs($e->amount) }}</b>@if (!$loop->last), @endif @endforeach
          </span>
        </div>
      @endif
    </section>
  </div>
@endsection
