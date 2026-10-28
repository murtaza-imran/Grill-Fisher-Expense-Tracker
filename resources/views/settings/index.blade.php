@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
  <div class="grid2 top">
    <div>
      <div class="card" data-acc>
        <div class="set-head"><span class="kpi-ic blue"><x-icon name="tag" size="16" /></span><div><h3>Expense categories <span class="cnt">{{ $categories->count() }}</span></h3><p>Tap Daily / Monthly to change a category's type.</p></div><span class="acc-chev mobile-only"><x-icon name="chevron-down" size="18" /></span></div>
        <div class="chips">
          @foreach ($categories as $c)
            <form method="POST" action="{{ route('settings.categories.destroy', $c) }}" class="chip" data-confirm="Remove category?" data-confirm-text="{{ $c->name }} will be removed from the list. Past expenses keep their category." data-confirm-ok="Remove">
              @csrf @method('DELETE') {{ $c->name }}
              <button type="submit" form="kind{{ $c->id }}" class="kind-btn {{ $c->kind }}" title="Change type">{{ $c->kind === 'monthly' ? 'Monthly' : 'Daily' }}</button>
              <button type="submit" aria-label="Remove {{ $c->name }}"><x-icon name="x" size="13" /></button>
            </form>
            <form id="kind{{ $c->id }}" method="POST" action="{{ route('settings.categories.kind', $c) }}" hidden>@csrf @method('PUT')</form>
          @endforeach
        </div>
        <form method="POST" action="{{ route('settings.categories.store') }}" class="row-btns">
          @csrf <input name="name" placeholder="New category" aria-label="New category" required>
          <select name="kind" aria-label="Type" class="kind-select"><option value="daily">Daily</option><option value="monthly">Monthly</option></select>
          <button class="btn btn-sm" type="submit"><x-icon name="plus" size="15" />Add</button>
        </form>
      </div>

      <div class="card" data-acc>
        <div class="set-head"><span class="kpi-ic"><x-icon name="lock" size="16" /></span><div><h3>Change password</h3><p>Use at least 8 characters.</p></div><span class="acc-chev mobile-only"><x-icon name="chevron-down" size="18" /></span></div>
        <form method="POST" action="{{ route('settings.password') }}">
          @csrf @method('PUT')
          <div class="field"><label for="current">Current password</label><input id="current" type="password" name="current" required autocomplete="current-password"></div>
          <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" required autocomplete="new-password"></div>
          <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
          <button class="btn btn-sm" type="submit">Update password</button>
        </form>
      </div>
    </div>

    <div>
      <div class="card" id="export" data-acc>
        <div class="set-head"><span class="kpi-ic gold"><x-icon name="download" size="16" /></span><div><h3>Export data</h3><p>Download your sales and expenses for any dates.</p></div><span class="acc-chev mobile-only"><x-icon name="chevron-down" size="18" /></span></div>
        <form method="GET" action="{{ route('export.form') }}" class="export-form">
          <div class="field"><span class="label">What to export</span>
            <div class="pay-opts">
              @foreach (['report' => 'Everything', 'sales' => 'Sales', 'expenses' => 'Expenses'] as $k => $v)
                <label><input type="radio" name="dataset" value="{{ $k }}" {{ $k === 'report' ? 'checked' : '' }}><span>{{ $v }}</span></label>
              @endforeach
            </div>
          </div>
          <div class="form-grid">
            <div class="field"><label for="exFrom">From</label><input type="date" id="exFrom" name="from" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="field"><label for="exTo">To</label><input type="date" id="exTo" name="to" value="{{ now()->toDateString() }}" required></div>
          </div>
          <span class="label section-gap" style="display:block;margin-bottom:8px">Download as</span>
          <div class="fmt-btns">
            @foreach ([['csv', 'CSV', 'file'], ['xlsx', 'Excel', 'chart'], ['pdf', 'PDF', 'printer'], ['docx', 'Word', 'file'], ['json', 'JSON', 'database']] as [$fmt, $lab, $ic])
              <button type="submit" name="format" value="{{ $fmt }}" class="fmt"><x-icon :name="$ic" size="18" />{{ $lab }}</button>
            @endforeach
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="card m-account mobile-only">
    <div class="ma-user"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></div></div>
    <form method="POST" action="{{ route('logout') }}" data-confirm="Log out?" data-confirm-text="You will need your email and password to log in again." data-confirm-ok="Log out" data-confirm-kind="neutral">@csrf<button class="btn btn-danger ma-out" type="submit"><x-icon name="logout" size="17" />Log out</button></form>
  </div>
@endsection
