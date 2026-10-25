@extends('layouts.app')
@section('title', 'Sales')
@section('heading', 'Sales')

@php
  $rs = fn ($n) => 'Rs. ' . number_format((int) $n);
  $by = fn ($p) => (int) $totals->$p;
@endphp

@section('toolbar')
  <form class="toolbar" method="GET">
    <label class="search"><x-icon name="search" size="16" /><input type="search" name="q" value="{{ $q }}" placeholder="Search BBQ, Fish, Other" aria-label="Search sales"></label>
    @include('partials.range', ['route' => 'sales.index', 'keep' => ['q' => $q]])
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
      <table class="tbl cards">
        <thead><tr><th>Date</th><th>Category</th><th>Payment</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
          @php $lastDay = null; $pageRows = $sales->getCollection(); @endphp
          @forelse ($sales as $s)
            @if ($s->date->toDateString() !== $lastDay)
              @php $lastDay = $s->date->toDateString(); $d = $s->date; $dayRows = $pageRows->filter(fn ($x) => $x->date->toDateString() === $lastDay); @endphp
              <tr class="grp"><td colspan="5"><span>{{ $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('D, j M')) }}</span><b>{{ $rs($dayRows->sum('total')) }} · {{ $dayRows->count() }} {{ Str::plural('order', $dayRows->count()) }}</b></td></tr>
            @endif
            <tr>
              <td class="date c-date">{{ $s->date->format('j M Y') }}</td>
              <td class="c-title">{{ $s->summary() }}</td>
              <td class="c-pill"><span class="pill {{ $s->payment }}">{{ ucfirst($s->payment) }}</span></td>
              <td class="num c-amt">{{ $rs($s->total) }}</td>
              <td class="act">
                <a href="{{ route('sales.edit', $s) }}" data-modal-form aria-label="Edit sale" title="Edit"><x-icon name="pencil" size="15" /></a>
                <form method="POST" action="{{ route('sales.destroy', $s) }}" class="inline" data-confirm="Delete this sale?" data-confirm-text="This sale of {{ $rs($s->total) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete sale">@csrf @method('DELETE')<button type="submit" class="del" aria-label="Delete sale" title="Delete"><x-icon name="trash" size="15" /></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="empty-state"><x-icon name="receipt" size="28" /><b>No sales in this period</b><span>Tap the button to add your first sale.</span><a href="{{ route('sales.create') }}" data-modal-form class="btn"><x-icon name="plus" size="16" />New sale</a></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('partials.pager', ['p' => $sales, 'what' => 'sales'])
  </div>
@endsection
