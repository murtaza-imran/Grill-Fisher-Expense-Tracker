{{-- Sale form: used as a full page and inside the pop-up ($modal = true) --}}
@php
  $editing = $sale->exists;
  $p = $modal ? 'm-' : '';
  $rows = old('items', $sale->items?->map(fn ($i) => ['name' => $i->name, 'qty' => $i->qty, 'price' => $i->price])->all() ?? []);
  $pay = old('payment', $sale->payment ?? 'cash');
@endphp
<form method="POST" action="{{ $editing ? route('sales.update', $sale) : route('sales.store') }}" class="sale-form {{ $modal ? 'js-ajax' : 'card page-card' }}">
  @csrf
  @if ($editing) @method('PUT') @endif
  @if ($modal)
    <div class="fm-head">
      <span class="kpi-ic gold"><x-icon name="receipt" size="17" /></span>
      <div><h3>{{ $editing ? 'Edit sale' : 'New sale' }}</h3><small>{{ $editing ? 'Change the items or payment and save.' : 'Tap menu items to add them to the order.' }}</small></div>
      <button type="button" class="circle sm" data-close-form aria-label="Close"><x-icon name="x" size="16" /></button>
    </div>
  @endif

  <div class="form-grid">
    <div class="field"><label for="{{ $p }}date">Date</label><input type="date" id="{{ $p }}date" name="date" value="{{ old('date', $sale->date?->toDateString()) }}" required></div>
    <div class="field"><label for="{{ $p }}customer">Customer <span class="opt">(optional)</span></label><input id="{{ $p }}customer" name="customer" value="{{ old('customer', $sale->customer) }}" placeholder="Customer name"></div>
  </div>

  <div class="section-gap">
    <span class="label">Add from menu</span>
    <div class="quick js-quick">
      @foreach ($menu as $m)
        <button type="button" data-name="{{ $m->name }}" data-price="{{ $m->price }}">{{ $m->name }} <span class="p">{{ number_format($m->price) }}</span></button>
      @endforeach
    </div>
  </div>

  <div class="items-box">
    <div class="table-wrap">
      <table class="tbl items-table">
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th class="num">Total</th><th></th></tr></thead>
        <tbody class="js-rows"></tbody>
      </table>
    </div>
    <p class="empty-msg js-norows">No items yet. Pick from the menu above or add a custom item.</p>
    <div class="items-foot">
      <button type="button" class="btn btn-ghost btn-sm js-add"><x-icon name="plus" size="15" />Custom item</button>
      <span class="grand-l">Total <strong class="js-grand">Rs. 0</strong></span>
    </div>
  </div>

  <div class="field section-gap"><span class="label">Payment</span>
    <div class="pay-opts">
      @foreach (['cash' => 'Cash', 'online' => 'Online'] as $k => $v)
        <label><input type="radio" name="payment" value="{{ $k }}" {{ $pay === $k ? 'checked' : '' }}><span>{{ $v }}</span></label>
      @endforeach
    </div>
  </div>
  <div class="field"><label for="{{ $p }}note">Note <span class="opt">(optional)</span></label><input id="{{ $p }}note" name="note" value="{{ old('note', $sale->note) }}" placeholder="e.g. home delivery"></div>

  <div class="form-actions">
    <button class="btn" type="submit">{{ $editing ? 'Save changes' : 'Save sale' }}</button>
    @if ($modal)
      <button type="button" class="btn btn-ghost" data-close-form>Cancel</button>
    @else
      <a href="{{ route('sales.index') }}" class="btn btn-ghost">Cancel</a>
    @endif
    <span class="spacer"></span>
    @if ($editing)
      <button class="btn btn-danger btn-sm" type="submit" form="{{ $p }}deleteSale"><x-icon name="trash" size="15" />Delete</button>
    @endif
  </div>
  <script type="application/json" class="js-initial">@json(array_values($rows))</script>
</form>
@if ($editing)
  <form id="{{ $p }}deleteSale" method="POST" action="{{ route('sales.destroy', $sale) }}" data-confirm="Delete this sale?" data-confirm-text="This sale of {{ 'Rs. ' . number_format($sale->total) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete sale">@csrf @method('DELETE')</form>
@endif
