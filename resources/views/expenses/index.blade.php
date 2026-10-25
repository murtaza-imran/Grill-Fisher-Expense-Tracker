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
    @include('partials.range', ['route' => 'expenses.index', 'keep' => ['category' => $cat]])
  </form>
@endsection

@section('content')
  @php $tot = (int) $totals->t; $paid = (int) $totals->paid; $pending = (int) $totals->pending; $pendingN = (int) $totals->pending_n; @endphp
  <div class="kpis kpis-3">
    <div class="kpi"><div class="kpi-top"><small>Total expenses</small><span class="kpi-ic blue"><x-icon name="trend-down" size="16" /></span></div><strong>{{ $rs($tot) }}</strong><span>{{ \Carbon\Carbon::parse($from)->format('j M') }} – {{ \Carbon\Carbon::parse($to)->format('j M') }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Paid</small><span class="kpi-ic good"><x-icon name="check" size="16" /></span></div><strong>{{ $rs($paid) }}</strong><span>{{ $tot ? round($paid / $tot * 100) . '% of total' : '—' }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Pending</small><span class="kpi-ic gold"><x-icon name="alert" size="16" /></span></div><strong class="{{ $pending ? 'pend' : '' }}">{{ $rs($pending) }}</strong><span>{{ $pendingN ? $pendingN . ' ' . Str::plural('bill', $pendingN) . ' still to pay' : 'Nothing pending' }}</span></div>
    <div class="kpi"><div class="kpi-top"><small>Entries</small><span class="kpi-ic "><x-icon name="receipt" size="16" /></span></div><strong>{{ number_format($totals->n) }}</strong><span>{{ $topCat ? 'Biggest: ' . $topCat : 'No expenses yet' }}</span></div>
  </div>
  <div class="card card-flush">
    <div class="table-wrap">
      <table class="tbl cards">
        <thead><tr><th>Date</th><th>Category</th><th>Paid to / note</th><th>Status</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
          @forelse ($expenses as $x)
            @php $detail = collect([$x->vendor, $x->note])->filter()->implode(' · '); @endphp
            <tr>
              <td class="date c-date">{{ $x->date->format('j M Y') }}</td>
              <td class="c-title">{{ $x->category }}</td>
              <td class="c-sub {{ $detail ? '' : 'sub' }}">{{ $detail ?: '—' }}</td>
              <td class="c-pill"><span class="pill {{ $x->status }}">{{ ucfirst($x->status) }}</span></td>
              <td class="num c-amt">{{ $rs($x->amount) }}</td>
              <td class="act">
                <a href="{{ route('expenses.edit', $x) }}" data-modal-form aria-label="Edit expense" title="Edit"><x-icon name="pencil" size="15" /></a>
                <form method="POST" action="{{ route('expenses.destroy', $x) }}" class="inline" data-confirm="Delete this expense?" data-confirm-text="This {{ $x->category }} expense of {{ $rs($x->amount) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete expense">@csrf @method('DELETE')<button type="submit" class="del" aria-label="Delete expense" title="Delete"><x-icon name="trash" size="15" /></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty-state"><x-icon name="wallet" size="28" /><b>No expenses in this period</b><span>Tap the button to add an expense.</span><a href="{{ route('expenses.create') }}" data-modal-form class="btn"><x-icon name="plus" size="16" />Add expense</a></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('partials.pager', ['p' => $expenses, 'what' => 'expenses'])
  </div>
@endsection
