@extends('layouts.app')
@section('title', 'Dashboard')

@php
  $rs = fn ($n) => 'Rs. ' . number_format((int) $n);
  $profitToday = $salesToday - $expToday;
  $profitMonth = $monthSales - $monthExp;
  // green/red change pill; $goodWhenUp = false for costs
  $pill = function (?int $d, string $vs, bool $goodWhenUp = true) {
      if ($d === null) return '<span class="delta flat">New</span><span class="vs">' . e($vs) . '</span>';
      $good = $d === 0 ? null : (($d > 0) === $goodWhenUp);
      $cls = $good === null ? 'flat' : ($good ? '' : 'down');
      return '<span class="delta ' . $cls . '">' . ($d > 0 ? '+' : '') . $d . '%</span><span class="vs">' . e($vs) . '</span>';
  };
  $catTotal = $cats->sum('v');
  $segs = $cats->take(2)->values()->map(fn ($c, $i) => $c + ['c' => ['var(--series-sales)', 'var(--series-exp)'][$i]]);
  if ($cats->count() > 2) $segs->push(['k' => 'Other', 'v' => $cats->slice(2)->sum('v'), 'c' => 'var(--series-other)']);
  $heatMax = max(1, max(array_map('max', $heat)));
  $level = fn ($n) => $n === 0 ? '' : 'l' . min(4, (int) ceil($n / $heatMax * 4));
  $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  $bestMax = max(1, (int) ($best->max('q') ?? 1));
  $start = \Carbon\Carbon::parse($daily[0]['d']);
@endphp

@section('pagehead')
  <div class="page-head">
    <div>
      <h1>Business Overview</h1>
    </div>
    <div class="actions">
      <span class="updated">Last updated: {{ now()->format('j F Y, g:i A') }}</span>
      <a href="{{ route('reports', ['q' => 'month']) }}" class="btn btn-ghost"><x-icon name="calendar" size="16" />{{ now()->startOfMonth()->format('j M') }} – {{ now()->format('j M') }}</a>
      <a href="{{ route('expenses.create') }}" data-modal-form class="btn btn-ghost"><x-icon name="plus" size="16" />Expense</a>
      <a href="{{ route('sales.create') }}" data-modal-form class="btn"><x-icon name="receipt" size="16" />New sale</a>
    </div>
  </div>
@endsection

@section('content')
  <div class="dash">
    <div class="dash-main">
      <section class="panel glow">
        <div class="card-head">
          <div><span class="eyebrow">Earnings</span><div class="title">Tracking our sales</div></div>
          <div class="seg" role="group" aria-label="Range">
            @foreach ([7, 14, 30] as $d)
              <a href="{{ route('dashboard', ['days' => $d]) }}" class="{{ $days === $d ? 'on' : '' }}">{{ $d }}D</a>
            @endforeach
            <a href="{{ route('reports') }}">View all</a>
          </div>
        </div>
        <div class="track-grid">
          <div class="tile chart-tile">
            <div class="chart-sum">
              <div>
                <span class="eyebrow">Total sales · {{ $start->format('j M') }} – {{ now()->format('j M') }}</span>
                <strong>{{ $rs($periodSales) }}</strong>
                <div class="foot">{!! $pill($dPeriod, 'vs previous ' . $days . ' days') !!}</div>
              </div>
              <a href="{{ route('sales.index') }}" class="circle sm" aria-label="Open sales"><x-icon name="arrow-out" size="15" /></a>
            </div>
            <div class="chart" data-chart="capsule" data-json='@json($daily)'></div>
            <details class="tbl-toggle"><summary>Show as table</summary>
              <div class="table-wrap"><table class="tbl"><thead><tr><th>Date</th><th class="num">Sales</th><th class="num">Expenses</th><th class="num">Profit</th></tr></thead>
                <tbody>@foreach (array_reverse($daily) as $r)<tr><td>{{ \Carbon\Carbon::parse($r['d'])->format('j M Y') }}</td><td class="num">{{ $rs($r['s']) }}</td><td class="num">{{ $rs($r['e']) }}</td><td class="num">{{ $rs($r['s'] - $r['e']) }}</td></tr>@endforeach</tbody>
              </table></div>
            </details>
          </div>

          <div class="tile assist">
            <span class="hi">Hi {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} ✦</span>
            <h4>What would you like to do today?</h4>
            <div class="acts">
              <a href="{{ route('sales.create') }}" data-modal-form class="chipbtn on"><span class="ci"><x-icon name="receipt" size="13" /></span>New sale</a>
              <a href="{{ route('expenses.create') }}" data-modal-form class="chipbtn"><span class="ci"><x-icon name="wallet" size="13" /></span>Add expense</a>
              <a href="{{ route('reports', ['q' => 'today']) }}" class="chipbtn"><span class="ci"><x-icon name="chart" size="13" /></span>Today's report</a>
              <a href="{{ route('reports', ['q' => 'month']) }}" class="chipbtn"><span class="ci"><x-icon name="calendar" size="13" /></span>This month</a>
              <a href="{{ route('settings') }}" class="chipbtn"><span class="ci"><x-icon name="utensils" size="13" /></span>Menu prices</a>
            </div>
            <form class="askbar" method="GET" action="{{ route('sales.index') }}" role="search">
              <input type="search" name="q" placeholder="Search a sale or item…" aria-label="Search sales">
              <button type="submit" aria-label="Search"><x-icon name="search" size="16" /></button>
            </form>
          </div>
        </div>
      </section>

      <div class="duo">
        <section class="panel">
          <div class="card-head">
            <div><span class="eyebrow">Expenses this month</span><div class="title">Where money goes</div></div>
            <a href="{{ route('expenses.index') }}" class="circle sm" aria-label="Open expenses"><x-icon name="arrow-out" size="15" /></a>
          </div>
          @if ($catTotal > 0)
            <div class="bigval"><strong><small>Rs.</small>{{ number_format($catTotal) }}</strong><span>{{ $cats->count() }} {{ Str::plural('category', $cats->count()) }}</span></div>
            <div class="segs">
              @foreach ($segs as $sg)
                <div class="seg-col" style="flex: {{ max(0.12, $sg['v'] / $catTotal) }}">
                  <div class="lab" title="{{ $sg['k'] }}">{{ $sg['k'] }}</div>
                  <div class="bar" style="background: {{ $sg['c'] }}"></div>
                </div>
              @endforeach
            </div>
            <div class="seg-legend">
              @foreach ($segs as $sg)
                <span><i style="background: {{ $sg['c'] }}"></i>{{ round($sg['v'] / $catTotal * 100) }}% · {{ $rs($sg['v']) }}</span>
              @endforeach
            </div>
          @else
            <div class="empty-state"><x-icon name="wallet" size="28" /><b>No expenses this month</b><span>Your spending split appears here.</span></div>
          @endif
        </section>

        <section class="panel">
          <div class="card-head">
            <div><span class="eyebrow">Busy hours · last 30 days</span><div class="title">Order time tracking</div></div>
          </div>
          <div class="busy">
            <div class="busy-sum">
              <strong>{{ number_format($orders30) }}</strong>
              <p>Orders in the last 30 days</p>
              {!! $pill($dOrders, 'vs before') !!}
              <div><a href="{{ route('reports') }}" class="btn btn-sm">View report</a></div>
            </div>
            <div>
              <div class="heat" role="img" aria-label="Orders by day and time">
                @foreach ($slots as $si => $slot)
                  <span class="rl">{{ $slot }}</span>
                  @foreach ([0, 1, 2, 3, 4, 5, 6] as $dw)
                    @php $n = $heat[$si][$dw]; @endphp
                    <span class="cell {{ $level($n) }}" data-tip="<b>{{ $dayNames[$dw] }}, {{ $slot }}</b><div class='r'><span>Orders</span>{{ $n }}</div>"></span>
                  @endforeach
                @endforeach
                <span></span>
                @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dl)<span class="cl">{{ $dl }}</span>@endforeach
              </div>
              <div class="heat-key">Less <i></i><i class="l1"></i><i class="l2"></i><i class="l3"></i><i class="l4"></i> More</div>
            </div>
          </div>
        </section>
      </div>

      <div class="duo duo-fill">
        <section class="panel">
          <div class="card-head">
            <div><span class="eyebrow">This month</span><div class="title">Best sellers</div></div>
          </div>
          <div class="best">
            @forelse ($best as $i => $b)
              <div class="best-row">
                <span class="rk">{{ $i + 1 }}</span>
                <div><b>{{ $b->name }}</b><div class="cap"><i style="width: {{ round($b->q / $bestMax * 100) }}%"></i></div></div>
                <span class="q">{{ $b->q }} sold<small>{{ $rs($b->v) }}</small></span>
              </div>
            @empty
              <div class="empty-state"><x-icon name="utensils" size="28" /><b>No sales yet</b><span>Top menu items will appear here.</span></div>
            @endforelse
          </div>
        </section>
        <section class="panel">
          <div class="card-head">
            <div><span class="eyebrow">This month</span><div class="title">Sales mix</div></div>
            <a href="{{ route('reports', ['q' => 'month']) }}" class="circle sm" aria-label="Open this month's report"><x-icon name="arrow-out" size="15" /></a>
          </div>
          @php $mixTotal = $mixCash + $mixOnline; @endphp
          @if ($mixTotal > 0)
            <div class="mix-bar" role="img" aria-label="Cash {{ round($mixCash / $mixTotal * 100) }}%, online {{ round($mixOnline / $mixTotal * 100) }}%">
              @if ($mixCash)<span class="cash" style="flex: {{ $mixCash }}"></span>@endif
              @if ($mixOnline)<span class="online" style="flex: {{ $mixOnline }}"></span>@endif
            </div>
            <div class="mix-legend">
              <div><i class="cash"></i><span>Cash</span><b>{{ $rs($mixCash) }}</b><small>{{ round($mixCash / $mixTotal * 100) }}%</small></div>
              <div><i class="online"></i><span>Online</span><b>{{ $rs($mixOnline) }}</b><small>{{ round($mixOnline / $mixTotal * 100) }}%</small></div>
            </div>
            <div class="mini-stats">
              <div><span class="eyebrow">Orders</span><strong>{{ number_format($monthOrders) }}</strong></div>
              <div><span class="eyebrow">Avg order</span><strong>{{ $rs($monthOrders ? $mixTotal / $monthOrders : 0) }}</strong></div>
              <div><span class="eyebrow">Items sold</span><strong>{{ number_format($itemsSold) }}</strong></div>
            </div>
          @else
            <div class="empty-state"><x-icon name="banknote" size="28" /><b>No sales this month</b><span>Cash and online split appears here.</span></div>
          @endif
        </section>
      </div>
    </div>

    <aside class="dash-side">
      <section class="panel">
        <div class="stats">
          <a class="stat" href="{{ route('sales.index') }}">
            <span class="eyebrow">Sales today</span><span class="circle sm go"><x-icon name="arrow-out" size="14" /></span>
            <div class="stat-val"><span class="ring"><x-icon name="trend-up" size="18" /></span><strong><small>Rs.</small>{{ number_format($salesToday) }}</strong></div>
            <div class="foot">{!! $pill($dSales, 'vs yesterday') !!}</div>
          </a>
          <a class="stat" href="{{ route('expenses.index') }}">
            <span class="eyebrow">Expenses today</span><span class="circle sm go"><x-icon name="arrow-out" size="14" /></span>
            <div class="stat-val"><span class="ring"><x-icon name="wallet" size="18" /></span><strong><small>Rs.</small>{{ number_format($expToday) }}</strong></div>
            <div class="foot">{!! $pill($dExp, 'vs yesterday', false) !!}</div>
          </a>
          <a class="stat" href="{{ route('reports', ['q' => 'today']) }}">
            <span class="eyebrow">Profit today</span><span class="circle sm go"><x-icon name="arrow-out" size="14" /></span>
            <div class="stat-val"><span class="ring"><x-icon name="banknote" size="18" /></span><strong class="{{ $profitToday < 0 ? 'neg' : '' }}"><small>Rs.</small>{{ $profitToday < 0 ? '−' : '' }}{{ number_format(abs($profitToday)) }}</strong></div>
            <div class="foot">{!! $pill($dProfit, 'vs yesterday') !!}</div>
          </a>
          <a class="stat" href="{{ route('reports', ['q' => 'month']) }}">
            <span class="eyebrow">Profit this month</span><span class="circle sm go"><x-icon name="arrow-out" size="14" /></span>
            <div class="stat-val"><span class="ring"><x-icon name="calendar" size="18" /></span><strong class="{{ $profitMonth < 0 ? 'neg' : '' }}"><small>Rs.</small>{{ $profitMonth < 0 ? '−' : '' }}{{ number_format(abs($profitMonth)) }}</strong></div>
            <div class="foot">{!! $pill($dMonth, 'vs last month') !!}</div>
          </a>
        </div>
      </section>

      <section class="panel">
        <div class="card-head">
          <div><span class="eyebrow">Latest entries</span><div class="title">Recent activity</div></div>
          <a href="{{ route('reports') }}" class="circle sm" aria-label="See all entries"><x-icon name="arrow-out" size="15" /></a>
        </div>
        <div class="rows">
          @forelse ($recent as $r)
            <a class="row-item" href="{{ $r['url'] }}" data-modal-form>
              <span class="av {{ $r['t'] }}"><x-icon :name="$r['t'] === 's' ? 'receipt' : 'wallet'" size="18" /></span>
              <div><b>{{ $r['title'] }}</b><small>{{ $r['date']->isToday() && $r['at'] ? $r['at']->diffForHumans() : $r['date']->format('j M') }}</small></div>
              <span class="status {{ strtolower($r['status']) }}">{{ $r['status'] }}</span>
              <span class="amt">{{ $r['amt'] < 0 ? '−' : '' }}{{ $rs(abs($r['amt'])) }}</span>
            </a>
          @empty
            <div class="empty-state"><x-icon name="receipt" size="28" /><b>No entries yet</b><span>Add your first sale or expense.</span></div>
          @endforelse
        </div>
      </section>

    </aside>
  </div>
@endsection
