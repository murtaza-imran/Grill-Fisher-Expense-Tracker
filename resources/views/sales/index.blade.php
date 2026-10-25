@extends('layouts.app')
@section('title', 'Sales')
@section('heading', 'Sales')

@php
  $rs = fn ($n) => 'Rs. ' . number_format((int) $n);
  $by = fn ($p) => (int) $totals->$p;
@endphp

@section('toolbar')
  <form class="toolbar" method="GET">
    <label class="search"><x-icon name="search" size="16" /><input type="search" name="q" value="{{ $q }}" placeholder="Search item or customer" aria-label="Search sales"></label>
    <input type="date" name="from" value="{{ $from }}" aria-label="From date"><span>to</span>
    <input type="date" name="to" value="{{ $to }}" aria-label="To date">
    <button class="btn btn-ghost btn-sm"><x-icon name="filter" size="15" />Filter</button>
  </form>
@endsection

@section('content')
  @php $tot = (int) $totals->t; $cnt = (int) $totals->n; @endphp
  <div class="kpis kpis-3">
    <div class="kpi"><div class="kpi-top"><small>Total sales</small><span class="kpi-ic gold"><x-icon name="trend-up" size="16" /></span></div><strong>{{ $rs($tot) }}</strong><span>{{ \Carbon\Carbon::parse($from)->format('j M') }} – {{ \Carbon\Carbon::parse($to)->format('j M') }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Orders</small><span class="kpi-ic "><x-icon name="receipt" size="16" /></span></div><strong>{{ number_format($cnt) }}</strong><span>Avg {{ $rs($cnt ? $tot / $cnt : 0) }} per order</span></div>
    <div class="kpi"><div class="kpi-top"><small>Cash</small><span class="kpi-ic good"><x-icon name="banknote" size="16" /></span></div><strong>{{ $rs($by('cash')) }}</strong><span>{{ $tot ? round($by('cash') / $tot * 100) . '% of total' : '—' }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Online</small><span class="kpi-ic blue"><x-icon name="wallet" size="16" /></span></div><strong>{{ $rs($by('online')) }}</strong><span>{{ $tot ? round($by('online') / $tot * 100) . '% of total' : '—' }}</span></div>
  </div>
  <div class="card card-flush">
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Items</th><th>Customer</th><th>Payment</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
          @forelse ($sales as $s)
            <tr>
              <td class="date">{{ $s->date->format('j M Y') }}</td>
              <td>{{ $s->summary() }}</td>
              <td class="{{ $s->customer ? '' : 'sub' }}">{{ $s->customer ?: '—' }}</td>
              <td><span class="pill {{ $s->payment }}">{{ ucfirst($s->payment) }}</span></td>
              <td class="num">{{ $rs($s->total) }}</td>
              <td class="act">
                <a href="{{ route('sales.edit', $s) }}" data-modal-form aria-label="Edit sale" title="Edit"><x-icon name="pencil" size="15" /></a>
                <form method="POST" action="{{ route('sales.destroy', $s) }}" class="inline" data-confirm="Delete this sale?" data-confirm-text="This sale of {{ $rs($s->total) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete sale">@csrf @method('DELETE')<button type="submit" class="del" aria-label="Delete sale" title="Delete"><x-icon name="trash" size="15" /></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty-state"><x-icon name="receipt" size="28" /><b>No sales in this period</b><span>Change the dates or add a new sale.</span></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('partials.pager', ['p' => $sales, 'what' => 'sales'])
  </div>
@endsection
