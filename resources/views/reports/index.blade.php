@extends('layouts.app')
@section('title', 'Reports')
@section('heading', 'Reports')

@php
  $rs = fn ($n) => 'Rs. ' . number_format((int) $n);
  $profit = $sales - $exp;
@endphp

@section('headActions')
  @include('partials.export', ['dataset' => 'report', 'params' => ['from' => $from, 'to' => $to]])
@endsection

@section('toolbar')
  <form class="toolbar" method="GET">
    <div class="seg" role="group" aria-label="Quick range">
      @foreach (['today' => 'Today', 'week' => '7 days', 'month' => 'This month', 'last' => 'Last month'] as $k => $v)
        <a href="{{ route('reports', ['q' => $k]) }}" class="{{ $q === $k ? 'on' : '' }}">{{ $v }}</a>
      @endforeach
      <button type="button" class="mobile-only {{ $q === '' ? 'on' : '' }}" data-toggle-dates>Custom</button>
    </div>
    <div class="date-range {{ $q === '' ? 'open' : '' }}">
      <input type="date" name="from" value="{{ $from }}" aria-label="From date"><span>to</span>
      <input type="date" name="to" value="{{ $to }}" aria-label="To date">
      <button class="btn btn-ghost btn-sm">Apply</button>
    </div>
  </form>
@endsection

@section('content')

  <div class="kpis kpis-3">
    <div class="kpi"><div class="kpi-top"><small>Sales</small><span class="kpi-ic gold"><x-icon name="trend-up" size="16" /></span></div><strong>{{ $rs($sales) }}</strong><span>{{ $salesN }} {{ Str::plural('sale', $salesN) }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Expenses</small><span class="kpi-ic blue"><x-icon name="trend-down" size="16" /></span></div><strong>{{ $rs($exp) }}</strong><span>{{ $expN }} {{ Str::plural('entry', $expN) }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Profit</small><span class="kpi-ic {{ $profit < 0 ? 'bad' : 'good' }}"><x-icon name="banknote" size="16" /></span></div><strong class="{{ $profit < 0 ? 'neg' : 'pos' }}">{{ $profit < 0 ? '−' : '' }}{{ $rs(abs($profit)) }}</strong><span>{{ $sales ? round($profit / $sales * 100) . '% margin' : 'No sales yet' }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Average daily sales</small><span class="kpi-ic"><x-icon name="calendar" size="16" /></span></div><strong>{{ $rs($sales / max(1, $days)) }}</strong><span>over {{ $days }} {{ Str::plural('day', $days) }}</span></div>
  </div>

  <div class="grid2">
    <div class="card"><div class="card-head"><h3>Expenses by category</h3></div>
      <div class="chart" data-chart="hbar" data-color="var(--series-exp)" data-empty="No expenses in this period." data-json='@json($cats)'></div></div>
    <div class="card"><div class="card-head"><h3>Sales by payment method</h3></div>
      <div class="chart" data-chart="hbar" data-color="var(--series-sales)" data-empty="No sales in this period." data-json='@json($pays)'></div></div>
  </div>

  <div class="card card-flush">
    <div class="card-head"><h3>All entries</h3><small>{{ \Carbon\Carbon::parse($from)->format('j M Y') }} – {{ \Carbon\Carbon::parse($to)->format('j M Y') }}</small></div>
    <div class="table-wrap">
      <table class="tbl cards">
        <thead><tr><th>Date</th><th>Type</th><th>Detail</th><th>Customer / paid to</th><th>Payment / status</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
          @forelse ($rows as $r)
            <tr>
              <td class="date c-date">{{ \Carbon\Carbon::parse($r['date'])->format('j M Y') }}</td>
              <td class="c-type">{{ $r['type'] }}</td>
              <td class="c-title">{{ $r['detail'] }}</td>
              <td class="c-sub {{ $r['party'] ? '' : 'sub' }}">{{ $r['party'] ?: '—' }}</td>
              <td class="c-pill"><span class="pill {{ strtolower($r['pay']) }}">{{ $r['pay'] }}</span></td>
              <td class="num c-amt" style="color:{{ $r['amount'] < 0 ? 'var(--bad)' : 'var(--good)' }}">{{ $r['amount'] < 0 ? '−' : '+' }}{{ $rs(abs($r['amount'])) }}</td>
              <td class="act">
                <a href="{{ $r['edit'] }}" data-modal-form aria-label="Edit {{ strtolower($r['type']) }}" title="Edit"><x-icon name="pencil" size="15" /></a>
                <form method="POST" action="{{ $r['del'] }}" class="inline" data-confirm="Delete this {{ strtolower($r['type']) }}?" data-confirm-text="{{ $r['detail'] }} ({{ $rs(abs($r['amount'])) }}) will be removed permanently. This cannot be undone." data-confirm-ok="Delete {{ strtolower($r['type']) }}">@csrf @method('DELETE')<button type="submit" class="del" aria-label="Delete {{ strtolower($r['type']) }}" title="Delete"><x-icon name="trash" size="15" /></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7"><div class="empty-state"><x-icon name="chart" size="28" /><b>Nothing in this period</b><span>Pick another date range.</span></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('partials.pager', ['p' => $rows, 'what' => 'entries'])
  </div>
@endsection
