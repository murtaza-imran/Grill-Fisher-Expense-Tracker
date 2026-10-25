@extends('layouts.app')
@section('title', 'Expenses')
@section('heading', 'Expenses')

@php $rs = fn ($n) => 'Rs. ' . number_format((int) $n); @endphp

@section('toolbar')
  <form class="toolbar" method="GET">
    <select name="category" aria-label="Category" onchange="this.form.submit()">
      <option value="">All categories</option>
      @foreach ($categories as $c)<option {{ $cat === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
    </select>
    <input type="date" name="from" value="{{ $from }}" aria-label="From date"><span>to</span>
    <input type="date" name="to" value="{{ $to }}" aria-label="To date">
    <button class="btn btn-ghost btn-sm"><x-icon name="filter" size="15" />Filter</button>
  </form>
@endsection

@section('content')
  @php $tot = (int) $totals->t; $cash = (int) $totals->cash; $online = (int) $totals->online; @endphp
  <div class="kpis kpis-3">
    <div class="kpi"><div class="kpi-top"><small>Total expenses</small><span class="kpi-ic blue"><x-icon name="trend-down" size="16" /></span></div><strong>{{ $rs($tot) }}</strong><span>{{ \Carbon\Carbon::parse($from)->format('j M') }} – {{ \Carbon\Carbon::parse($to)->format('j M') }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Entries</small><span class="kpi-ic "><x-icon name="receipt" size="16" /></span></div><strong>{{ number_format($totals->n) }}</strong><span>{{ $topCat ? 'Biggest: ' . $topCat : 'No expenses yet' }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Paid cash</small><span class="kpi-ic good"><x-icon name="banknote" size="16" /></span></div><strong>{{ $rs($cash) }}</strong><span>{{ $tot ? round($cash / $tot * 100) . '% of total' : '—' }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Paid online</small><span class="kpi-ic gold"><x-icon name="wallet" size="16" /></span></div><strong>{{ $rs($online) }}</strong><span>{{ $tot ? round($online / $tot * 100) . '% of total' : '—' }}</span></div>
  </div>
  <div class="card card-flush">
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Category</th><th>Paid to / note</th><th>Payment</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
          @forelse ($expenses as $x)
            @php $detail = collect([$x->vendor, $x->note])->filter()->implode(' · '); @endphp
            <tr>
              <td class="date">{{ $x->date->format('j M Y') }}</td>
              <td>{{ $x->category }}</td>
              <td class="{{ $detail ? '' : 'sub' }}">{{ $detail ?: '—' }}</td>
              <td><span class="pill {{ $x->payment }}">{{ ucfirst($x->payment) }}</span></td>
              <td class="num">{{ $rs($x->amount) }}</td>
              <td class="act">
                <a href="{{ route('expenses.edit', $x) }}" data-modal-form aria-label="Edit expense" title="Edit"><x-icon name="pencil" size="15" /></a>
                <form method="POST" action="{{ route('expenses.destroy', $x) }}" class="inline" data-confirm="Delete this expense?" data-confirm-text="This {{ $x->category }} expense of {{ $rs($x->amount) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete expense">@csrf @method('DELETE')<button type="submit" class="del" aria-label="Delete expense" title="Delete"><x-icon name="trash" size="15" /></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty-state"><x-icon name="wallet" size="28" /><b>No expenses in this period</b><span>Change the dates or add a new expense.</span></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('partials.pager', ['p' => $expenses, 'what' => 'expenses'])
  </div>
@endsection
