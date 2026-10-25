@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
  <div class="grid2 top">
    <div class="card">
      <div class="set-head"><span class="kpi-ic gold"><x-icon name="utensils" size="16" /></span><div><h3>Menu items</h3><p>Prices used when you tap an item on the sale screen.</p></div></div>
      @foreach ($items as $it)
        <form method="POST" action="{{ route('settings.items.update', $it) }}" class="item-edit">
          @csrf @method('PUT')
          <input name="name" value="{{ $it->name }}" aria-label="Item name" required>
          <input name="price" type="number" min="0" value="{{ $it->price }}" aria-label="Price" required>
          <button class="btn btn-ghost btn-sm" type="submit">Save</button>
          <button class="btn btn-ghost btn-sm del" type="submit" form="delItem{{ $it->id }}" aria-label="Remove {{ $it->name }}" title="Remove"><x-icon name="trash" size="15" /></button>
        </form>
        <form id="delItem{{ $it->id }}" method="POST" action="{{ route('settings.items.destroy', $it) }}" data-confirm="Remove menu item?" data-confirm-text="{{ $it->name }} will be removed from the sale screen. Past sales are not affected." data-confirm-ok="Remove">@csrf @method('DELETE')</form>
      @endforeach
      <form method="POST" action="{{ route('settings.items.store') }}" class="row-btns">
        @csrf
        <input name="name" placeholder="New item name" aria-label="New item name" required>
        <input name="price" type="number" min="0" placeholder="Price" aria-label="New item price" required style="flex:0 0 110px;min-width:0">
        <button class="btn btn-sm" type="submit"><x-icon name="plus" size="15" />Add item</button>
      </form>
    </div>

    <div>
      <div class="card">
        <div class="set-head"><span class="kpi-ic blue"><x-icon name="tag" size="16" /></span><div><h3>Expense categories</h3><p>Used when you add an expense.</p></div></div>
        <div class="chips">
          @foreach ($categories as $c)
            <form method="POST" action="{{ route('settings.categories.destroy', $c) }}" class="chip" data-confirm="Remove category?" data-confirm-text="{{ $c->name }} will be removed from the list. Past expenses keep their category." data-confirm-ok="Remove">
              @csrf @method('DELETE') {{ $c->name }} <button type="submit" aria-label="Remove {{ $c->name }}"><x-icon name="x" size="13" /></button>
            </form>
          @endforeach
        </div>
        <form method="POST" action="{{ route('settings.categories.store') }}" class="row-btns">
          @csrf <input name="name" placeholder="New category" aria-label="New category" required><button class="btn btn-sm" type="submit"><x-icon name="plus" size="15" />Add</button>
        </form>
      </div>

      <div class="card">
        <div class="set-head"><span class="kpi-ic"><x-icon name="lock" size="16" /></span><div><h3>Change password</h3><p>Use at least 8 characters.</p></div></div>
        <form method="POST" action="{{ route('settings.password') }}">
          @csrf @method('PUT')
          <div class="field"><label for="current">Current password</label><input id="current" type="password" name="current" required autocomplete="current-password"></div>
          <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" required autocomplete="new-password"></div>
          <div class="field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
          <button class="btn btn-sm" type="submit">Update password</button>
        </form>
      </div>

      <div class="card" id="export">
        <div class="set-head"><span class="kpi-ic gold"><x-icon name="download" size="16" /></span><div><h3>Export data</h3><p>Download your sales and expenses for any dates.</p></div></div>
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
@endsection
