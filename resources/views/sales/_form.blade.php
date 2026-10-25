{{-- Sale form: used as a full page and inside the pop-up ($modal = true) --}}
@php
  $editing = $sale->exists;
  $p = $modal ? 'm-' : '';
  $amounts = old('amounts', $editing ? $sale->categoryAmounts() : []);
  $pay = old('payment', $sale->payment ?? 'cash');
  $icons = ['BBQ' => 'flame', 'Fish' => 'fish', 'Other' => 'receipt'];
@endphp
<form method="POST" action="{{ $editing ? route('sales.update', $sale) : route('sales.store') }}" class="sale-form {{ $modal ? 'js-ajax' : 'card page-card' }}">
  @csrf
  @if ($editing) @method('PUT') @endif
  @if ($modal)
    <div class="fm-head">
      <span class="kpi-ic gold"><x-icon name="receipt" size="17" /></span>
      <div><h3>{{ $editing ? 'Edit sale' : 'New sale' }}</h3><small>{{ $editing ? 'Change the details and save.' : 'Tap what you sold — one or more — and enter amounts.' }}</small></div>
      <button type="button" class="circle sm" data-close-form aria-label="Close"><x-icon name="x" size="16" /></button>
    </div>
  @endif

  <div class="field f-menu"><span class="label">What did you sell? <span class="opt">(tap one or more)</span></span>
    <div class="pay-opts cat-opts">
      @foreach (\App\Models\Sale::CATEGORIES as $c)
        <label><input type="checkbox" class="cat-toggle" value="{{ $c }}" {{ !empty($amounts[$c]) ? 'checked' : '' }}><span><x-icon :name="$icons[$c]" size="18" />{{ $c }}</span></label>
      @endforeach
    </div>
  </div>

  <div class="f-items cat-amounts">
    @foreach (\App\Models\Sale::CATEGORIES as $c)
      <div class="field cat-amount" data-cat="{{ $c }}" {{ empty($amounts[$c]) ? 'hidden' : '' }}>
        <label for="{{ $p }}amt{{ $c }}"><x-icon :name="$icons[$c]" size="15" />{{ $c }} amount (Rs.)</label>
        <input type="number" id="{{ $p }}amt{{ $c }}" name="amounts[{{ $c }}]" class="amount-input" min="1" inputmode="numeric" value="{{ $amounts[$c] ?? '' }}" placeholder="0" {{ empty($amounts[$c]) ? '' : 'required' }}>
      </div>
    @endforeach
    <p class="cat-hint js-cat-hint" {{ array_filter($amounts) ? 'hidden' : '' }}>Tap BBQ, Fish or Other above to enter the amount.</p>
    <div class="cat-total js-cat-totalrow" {{ count(array_filter($amounts)) > 1 ? '' : 'hidden' }}><span>Total</span><b class="js-cat-total">Rs. {{ number_format(array_sum($amounts)) }}</b></div>
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
    <span class="fa-total mobile-only"><small>Total</small><b class="js-cat-total">Rs. {{ number_format(array_sum($amounts)) }}</b></span>
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
