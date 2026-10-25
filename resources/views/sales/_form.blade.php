{{-- Sale form: used as a full page and inside the pop-up ($modal = true) --}}
@php
  $editing = $sale->exists;
  $p = $modal ? 'm-' : '';
  $category = old('category', $editing ? $sale->category() : null);
  $pay = old('payment', $sale->payment ?? 'cash');
  $icons = ['BBQ' => 'flame', 'Fish' => 'fish', 'Other' => 'receipt'];
@endphp
<form method="POST" action="{{ $editing ? route('sales.update', $sale) : route('sales.store') }}" class="sale-form {{ $modal ? 'js-ajax' : 'card page-card' }}">
  @csrf
  @if ($editing) @method('PUT') @endif
  @if ($modal)
    <div class="fm-head">
      <span class="kpi-ic gold"><x-icon name="receipt" size="17" /></span>
      <div><h3>{{ $editing ? 'Edit sale' : 'New sale' }}</h3><small>{{ $editing ? 'Change the details and save.' : 'Choose what you sold and enter the amount.' }}</small></div>
      <button type="button" class="circle sm" data-close-form aria-label="Close"><x-icon name="x" size="16" /></button>
    </div>
  @endif

  <div class="field f-menu"><span class="label">What did you sell?</span>
    <div class="pay-opts cat-opts">
      @foreach (\App\Models\Sale::CATEGORIES as $c)
        <label><input type="radio" name="category" value="{{ $c }}" {{ $category === $c ? 'checked' : '' }} required><span><x-icon :name="$icons[$c]" size="18" />{{ $c }}</span></label>
      @endforeach
    </div>
  </div>

  <div class="field f-items"><label for="{{ $p }}amount">Amount (Rs.)</label>
    <input type="number" id="{{ $p }}amount" name="amount" class="amount-input" min="1" inputmode="numeric" value="{{ old('amount', $editing ? $sale->total : '') }}" placeholder="0" required>
  </div>

  <div class="field f-pay"><span class="label">Payment</span>
    <div class="pay-opts">
      @foreach (['cash' => 'Cash', 'online' => 'Online'] as $k => $v)
        <label><input type="radio" name="payment" value="{{ $k }}" {{ $pay === $k ? 'checked' : '' }}><span>{{ $v }}</span></label>
      @endforeach
    </div>
  </div>

  <div class="field f-meta"><label for="{{ $p }}date">Date</label><input type="date" id="{{ $p }}date" name="date" value="{{ old('date', $sale->date?->toDateString()) }}" required></div>

  <div class="form-actions f-actions">
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
</form>
@if ($editing)
  <form id="{{ $p }}deleteSale" method="POST" action="{{ route('sales.destroy', $sale) }}" data-confirm="Delete this sale?" data-confirm-text="This sale of {{ 'Rs. ' . number_format($sale->total) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete sale">@csrf @method('DELETE')</form>
@endif
