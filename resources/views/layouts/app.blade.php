<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') | Grill Fisher &amp; BBQ</title>
  <meta name="theme-color" content="#0a0806">
  <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
  <link rel="apple-touch-icon" href="{{ asset('img/icon-180.png') }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Grill Fisher">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=26">
</head>
<body class="page-{{ str_replace('.', '-', request()->route()?->getName() ?? 'page') }}">
@php
  $nav = [
    ['dashboard', 'Dashboard', 'dashboard'],
    ['sales.index', 'Sales', 'sales.*'],
    ['expenses.index', 'Expenses', 'expenses.*'],
    ['reports', 'Reports', 'reports*'],
    ['settings', 'Settings', 'settings*'],
  ];
  $user = auth()->user();
@endphp
  <div class="shell">
    <header class="topbar">
      <a href="{{ route('dashboard') }}" class="logo">
        <img src="{{ asset('img/logo.webp') }}" alt="" width="46" height="46">
        <div><b>Grill Fisher &amp; BBQ</b><small>Business dashboard</small></div>
      </a>
      <div class="appbar-title mobile-only">@yield('heading', 'Dashboard')</div>
      <nav class="pillnav" aria-label="Sections">
        @foreach ($nav as [$route, $label, $pattern])
          <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
      </nav>
      <div class="topbar-right">
        <a href="{{ route('settings') }}" class="circle mobile-only {{ request()->routeIs('settings*') ? 'on' : '' }}" aria-label="Settings" title="Settings"><x-icon name="settings" size="17" /></a>
        @include('partials.export', ['dataset' => 'report', 'round' => true, 'label' => 'This month · download as', 'params' => ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]])
        <div class="me">
          <span class="avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
          <div><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div>
        </div>
        <form method="POST" action="{{ route('logout') }}" data-confirm="Log out?" data-confirm-text="You will need your email and password to log in again." data-confirm-ok="Log out" data-confirm-kind="neutral">@csrf<button type="submit" class="circle" aria-label="Log out" title="Log out"><x-icon name="logout" size="17" /></button></form>
      </div>
    </header>

    @if (session('ok'))
      <div class="flash" role="status"><x-icon name="check" />{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
      <div class="errors" role="alert"><x-icon name="alert" /><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @hasSection('pagehead')
      @yield('pagehead')
    @elseif (View::hasSection('toolbar'))
      {{-- pages with filters: title on top, then filters + buttons in one row --}}
      <div class="page-head">
        <div>
          <h1>@yield('heading', 'Dashboard')</h1>
          <div class="sub">{{ now()->format('l, j F Y') }}</div>
        </div>
      </div>
      <div class="bar">
        @yield('toolbar')
        <div class="actions">
          @yield('headActions')
          @unless (request()->routeIs('sales.*'))
            <a href="{{ route('expenses.create') }}" data-modal-form class="btn {{ request()->routeIs('expenses.*') ? '' : 'btn-ghost' }}"><x-icon name="plus" size="16" />{{ request()->routeIs('expenses.*') ? 'Add expense' : 'Expense' }}</a>
          @endunless
          @unless (request()->routeIs('expenses.*'))
            <a href="{{ route('sales.create') }}" data-modal-form class="btn"><x-icon name="plus" size="16" />New sale</a>
          @endunless
        </div>
      </div>
    @else
      <div class="page-head">
        <div>
          <h1>@yield('heading', 'Dashboard')</h1>
          <div class="sub">{{ now()->format('l, j F Y') }}</div>
        </div>
        <div class="actions">
          @yield('headActions')
          @unless (request()->routeIs('sales.*'))
            <a href="{{ route('expenses.create') }}" data-modal-form class="btn {{ request()->routeIs('expenses.*') ? '' : 'btn-ghost' }}"><x-icon name="plus" size="16" />{{ request()->routeIs('expenses.*') ? 'Add expense' : 'Expense' }}</a>
          @endunless
          @unless (request()->routeIs('expenses.*'))
            <a href="{{ route('sales.create') }}" data-modal-form class="btn"><x-icon name="plus" size="16" />New sale</a>
          @endunless
        </div>
      </div>
    @endif

    @yield('content')
  </div>

  <nav class="bnav" aria-label="Quick">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard" size="20" />Home</a>
    <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}"><x-icon name="receipt" size="20" />Sales</a>
    @if (request()->routeIs('expenses.*'))
      <a href="{{ route('expenses.create') }}" data-modal-form class="bnav-add" aria-label="Add expense"><x-icon name="plus" size="24" /></a>
    @elseif (request()->routeIs('sales.*'))
      <a href="{{ route('sales.create') }}" data-modal-form class="bnav-add" aria-label="New sale"><x-icon name="plus" size="24" /></a>
    @else
      <button type="button" class="bnav-add" data-open-sheet aria-label="Add sale or expense"><x-icon name="plus" size="24" /></button>
    @endif
    <a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}"><x-icon name="wallet" size="20" />Expenses</a>
    <a href="{{ route('reports') }}" class="{{ request()->routeIs('reports*') ? 'active' : '' }}"><x-icon name="chart" size="20" />Reports</a>
  </nav>

  <div class="tip" id="tip" role="tooltip" hidden></div>

  {{-- phone "+" choice: what to add --}}
  <div class="sheet" id="addSheet" hidden>
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Add new">
      <span class="sheet-grab"></span>
      <h3>What do you want to add?</h3>
      <a href="{{ route('sales.create') }}" data-modal-form class="sheet-opt sale"><span class="so-ic"><x-icon name="receipt" size="22" /></span><span><b>New sale</b><small>Customer order or takeaway</small></span><x-icon name="chevron-right" size="18" /></a>
      <a href="{{ route('expenses.create') }}" data-modal-form class="sheet-opt exp"><span class="so-ic"><x-icon name="wallet" size="22" /></span><span><b>Add expense</b><small>Fish, gas, salary, bills…</small></span><x-icon name="chevron-right" size="18" /></a>
      <button type="button" class="btn btn-ghost sheet-cancel" data-close-sheet>Cancel</button>
    </div>
  </div>

  <div class="form-modal" id="formModal" hidden>
    <div class="form-modal-card" role="dialog" aria-modal="true" aria-label="Form">
      <div class="fm-body" id="formModalBody"></div>
    </div>
  </div>

  <div class="modal" id="modal" hidden>
    <div class="modal-card" role="alertdialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalText">
      <span class="modal-ic danger" id="modalIc">
        <x-icon name="trash" size="22" class="ic-danger" /><x-icon name="logout" size="22" class="ic-neutral" /><x-icon name="alert" size="22" class="ic-info" /><x-icon name="check" size="26" class="ic-success" />
      </span>
      <h3 id="modalTitle"></h3>
      <strong class="modal-amount" id="modalAmount" hidden></strong>
      <p id="modalText"></p>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" id="modalCancel">Cancel</button>
        <button type="button" class="btn btn-confirm" id="modalOk">Confirm</button>
      </div>
    </div>
  </div>
  @if (session('saved'))
    <script type="application/json" id="savedPopup">@json(session('saved'))</script>
  @endif
  <script src="{{ asset('js/app.js') }}?v=22"></script>
  @stack('scripts')
</body>
</html>
