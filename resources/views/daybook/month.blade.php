@extends('layouts.app')
@section('title', 'Month')
@section('heading', 'Month')

@php
  $rs = fn ($n) => 'Rs. ' . number_format((int) $n);
  $profit = $totCash - $fixedTotal;
@endphp

@section('toolbar')
  <form class="toolbar db-nav" method="GET" action="{{ route('daybook.month') }}">
    <a href="{{ route('daybook.month', ['m' => $prev]) }}" class="circle" aria-label="Previous month"><x-icon name="arrow-left" size="17" /></a>
    <div class="db-date"><b>{{ $m->format('F') }}</b><span>{{ $m->format('Y') }}</span></div>
    @if ($next)
      <a href="{{ route('daybook.month', ['m' => $next]) }}" class="circle" aria-label="Next month"><x-icon name="arrow-left" size="17" style="transform: rotate(180deg)" /></a>
    @else
      <span class="circle db-off" aria-hidden="true"><x-icon name="arrow-left" size="17" style="transform: rotate(180deg)" /></span>
    @endif
    <input type="month" name="m" value="{{ $m->format('Y-m') }}" max="{{ now()->format('Y-m') }}" aria-label="Pick a month" onchange="this.form.submit()">
    <a href="{{ route('reports', ['q' => 'month']) }}" class="btn btn-ghost btn-sm"><x-icon name="chart" size="15" />Reports</a>
  </form>
@endsection

@section('content')
  {{-- all day cash − monthly costs = real profit --}}
  <div class="kpis kpis-3 db-kpis">
    <div class="kpi">
      <div class="kpi-top"><small>Total sale</small><span class="kpi-ic gold"><x-icon name="trend-up" size="16" /></span></div>
      <strong>{{ $rs($totSale) }}</strong><span>{{ $daysWithSales }} {{ Str::plural('day', $daysWithSales) }} with sales</span>
    </div>
    <div class="kpi">
      <div class="kpi-top"><small>Daily expenses</small><span class="kpi-ic blue"><x-icon name="wallet" size="16" /></span></div>
      <strong>{{ $rs($totExp) }}</strong><span>Day cash {{ $rs($totCash) }}</span>
    </div>
    <div class="kpi">
      <div class="kpi-top"><small>Monthly costs</small><span class="kpi-ic"><x-icon name="calendar" size="16" /></span></div>
      <strong>{{ $rs($fixedTotal) }}</strong><span>Rent, salary, bills, stock</span>
    </div>
    <div class="kpi db-main">
      <div class="kpi-top"><small>Real profit</small><span class="kpi-ic {{ $profit < 0 ? 'bad' : 'good' }}"><x-icon name="banknote" size="16" /></span></div>
      <strong class="{{ $profit < 0 ? 'neg' : 'pos' }}">{{ $profit < 0 ? '−' : '' }}{{ $rs(abs($profit)) }}</strong><span>Day cash − monthly costs</span>
    </div>
  </div>

  @if ($perDay)
    <section class="card db-status">
      <div class="db-status-main">
        <div class="db-status-text">
          <b>Daily target {{ $rs($perDay) }} <span class="muted">— {{ $rs($fixedTotal) }} ÷ {{ $m->daysInMonth }} days</span></b>
          <div class="db-bar"><i style="width: {{ $daysWithSales ? round($hitTarget / $daysWithSales * 100) : 0 }}%" class="ok"></i></div>
          <small>{{ $hitTarget }} of {{ $daysWithSales }} {{ Str::plural('day', $daysWithSales) }} reached the target</small>
        </div>
      </div>
    </section>
  @endif

  <div class="grid2 db-cols">
    <section class="card card-flush">
      <div class="card-head"><div><span class="eyebrow">Tap a day to open it</span><div class="title">Day by day</div></div></div>
      <div class="table-wrap">
        <table class="tbl db-days">
          <thead><tr><th>Day</th><th class="num">Sale</th><th class="num">Daily exp.</th><th class="num">Day cash</th><th></th></tr></thead>
          <tbody>
            @foreach ($days as $r)
              @php $hit = $perDay > 0 && $r['sale'] > 0 && $r['cash'] >= $perDay; $url = route('daybook', ['date' => $r['d']->toDateString()]); @endphp
              <tr class="{{ $r['sale'] || $r['exp'] ? '' : 'db-blank' }}" onclick="location.href='{{ $url }}'">
                <td><a href="{{ $url }}" class="db-day"><b>{{ $r['d']->format('j M') }}</b><small>{{ $r['d']->isToday() ? 'Today' : $r['d']->format('D') }}</small></a></td>
                <td class="num">{{ $r['sale'] ? $rs($r['sale']) : '—' }}</td>
                <td class="num db-neg">{{ $r['exp'] ? $rs($r['exp']) : '—' }}</td>
                <td class="num"><b class="{{ $r['cash'] < 0 ? 'neg' : ($hit ? 'pos' : '') }}">{{ $r['sale'] || $r['exp'] ? $rs($r['cash']) : '—' }}</b></td>
                <td class="db-flag">@if ($r['closed'])<span title="Day closed"><x-icon name="check" size="15" /></span>@endif</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot><tr><td><b>Total</b></td><td class="num"><b>{{ $rs($totSale) }}</b></td><td class="num db-neg"><b>{{ $rs($totExp) }}</b></td><td class="num"><b>{{ $rs($totCash) }}</b></td><td></td></tr></tfoot>
        </table>
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <div><span class="eyebrow">{{ $fixed->count() }} {{ Str::plural('cost', $fixed->count()) }} · {{ $rs($fixedTotal) }}</span><div class="title">Monthly costs</div></div>
        <a href="{{ route('expenses.create', ['kind' => 'monthly', 'date' => $m->isSameMonth(now()) ? now()->toDateString() : $m->toDateString()]) }}" data-modal-form class="btn btn-ghost btn-sm"><x-icon name="plus" size="15" />Add</a>
      </div>
      <div class="rows">
        @forelse ($fixed as $e)
          <a class="row-item" href="{{ route('expenses.edit', $e) }}" data-modal-form>
            <span class="av e"><x-icon name="calendar" size="18" /></span>
            <div><b>{{ $e->category }}</b><small>{{ $e->date->format('j M') }}{{ $e->vendor ? ' · ' . $e->vendor : '' }}</small></div>
            <span class="status {{ $e->status === 'pending' ? 'expense' : 'cash' }}">{{ ucfirst($e->status) }}</span>
            <span class="amt">{{ $rs($e->amount) }}</span>
          </a>
        @empty
          <div class="empty-state"><x-icon name="calendar" size="26" /><b>No monthly costs for {{ $m->format('F') }}</b><span>Add rent, salaries, electricity and bulk stock here.</span></div>
        @endforelse
      </div>
    </section>
  </div>
@endsection
