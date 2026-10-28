{{-- Expense form: used as a full page and inside the pop-up ($modal = true) --}}
@php
  $editing = $expense->exists;
  $p = $modal ? 'm-' : '';
  $status = old('status', $expense->status ?? 'paid');
  // new expense: pick the first category of the asked type; type follows the category unless chosen
  $selCat = old('category', $expense->category ?? ($expense->kind ? optional($categories->firstWhere('kind', $expense->kind))->name : null));
  $kind = old('kind', $expense->kind ?: (optional($categories->firstWhere('name', $selCat ?? $categories->first()?->name))->kind ?? 'daily'));
@endphp
<form method="POST" action="{{ $editing ? route('expenses.update', $expense) : route('expenses.store') }}" class="expense-form {{ $modal ? 'js-ajax' : 'card page-card' }}">
  @csrf
  @if ($editing) @method('PUT') @endif
  @if ($modal)
    <div class="fm-head">
      <span class="kpi-ic blue"><x-icon name="wallet" size="17" /></span>
      <div><h3>{{ $editing ? 'Edit expense' : 'Add expense' }}</h3><small>{{ $editing ? 'Change the details and save.' : 'Record money spent on the business.' }}</small></div>
      <button type="button" class="circle sm" data-close-form aria-label="Close"><x-icon name="x" size="16" /></button>
    </div>
  @endif

  <div class="form-grid">
    <div class="field"><label for="{{ $p }}amount">Amount (Rs.)</label><input type="number" id="{{ $p }}amount" name="amount" min="1" value="{{ old('amount', $expense->amount) }}" placeholder="0" required autofocus></div>
    <div class="field"><label for="{{ $p }}date">Date</label><input type="date" id="{{ $p }}date" name="date" value="{{ old('date', $expense->date?->toDateString()) }}" required></div>
    <div class="field"><label for="{{ $p }}category">Category</label>
      <select id="{{ $p }}category" name="category" required data-kind-sync>
        @foreach ($categories as $c)<option data-kind="{{ $c->kind }}" {{ $selCat === $c->name ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
      </select>
    </div>
    <div class="field"><label for="{{ $p }}vendor">Paid to <span class="opt">(optional)</span></label><input id="{{ $p }}vendor" name="vendor" value="{{ old('vendor', $expense->vendor) }}" placeholder="e.g. fish market, staff name"></div>
    <div class="field full"><span class="label">Type</span>
      <div class="pay-opts kind-opts">
        <label><input type="radio" name="kind" value="daily" {{ $kind === 'daily' ? 'checked' : '' }}><span><b>Daily</b><small>Taken from today's sale</small></span></label>
        <label><input type="radio" name="kind" value="monthly" {{ $kind === 'monthly' ? 'checked' : '' }}><span><b>Monthly</b><small>Rent, salary, bills, stock</small></span></label>
      </div>
    </div>
    <div class="field full"><span class="label">Status</span>
      <div class="pay-opts">
        @foreach (['paid' => 'Paid', 'pending' => 'Pending'] as $k => $v)
          <label><input type="radio" name="status" value="{{ $k }}" {{ $status === $k ? 'checked' : '' }}><span>{{ $v }}</span></label>
        @endforeach
      </div>
      <span class="hint">Pending = you still have to pay this amount.</span>
    </div>
    <div class="field full"><label for="{{ $p }}note">Note <span class="opt">(optional)</span></label><input id="{{ $p }}note" name="note" value="{{ old('note', $expense->note) }}" placeholder="e.g. 10 kg rohu"></div>
  </div>

  <div class="form-actions f-actions">
    <button class="btn" type="submit">{{ $editing ? 'Save changes' : 'Save expense' }}</button>
    @if ($modal)
      <button type="button" class="btn btn-ghost" data-close-form>Cancel</button>
    @else
      <a href="{{ route('expenses.index') }}" class="btn btn-ghost">Cancel</a>
    @endif
    <span class="spacer"></span>
    @if ($editing)
      <button class="btn btn-danger btn-sm" type="submit" form="{{ $p }}deleteExpense"><x-icon name="trash" size="15" />Delete</button>
    @endif
  </div>
</form>
@if ($editing)
  <form id="{{ $p }}deleteExpense" method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm="Delete this expense?" data-confirm-text="This {{ $expense->category }} expense of {{ 'Rs. ' . number_format($expense->amount) }} will be removed permanently. This cannot be undone." data-confirm-ok="Delete expense">@csrf @method('DELETE')</form>
@endif
