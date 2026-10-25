{{-- Export dropdown: $dataset = report|sales|expenses, $params = current filters, $round = icon-only header button, $label = menu heading --}}
@php
  $formats = [
    ['csv', 'CSV', 'file'],
    ['xlsx', 'Excel', 'chart'],
    ['pdf', 'PDF', 'printer'],
    ['docx', 'Word', 'file'],
    ['json', 'JSON', 'database'],
  ];
@endphp
<div class="dd">
  @if ($round ?? false)
    <button type="button" class="circle" data-dd aria-haspopup="menu" aria-expanded="false" aria-label="Export" title="Export"><x-icon name="download" size="17" /></button>
  @else
    <button type="button" class="btn btn-ghost btn-sm" data-dd aria-haspopup="menu" aria-expanded="false">
      <x-icon name="download" size="15" />Export<x-icon name="chevron-down" size="14" class="dd-caret" />
    </button>
  @endif
  <div class="dd-menu" role="menu" hidden>
    <span class="dd-label">{{ $label ?? 'Download as' }}</span>
    @foreach ($formats as [$fmt, $fmtName, $icon])
      <a role="menuitem" href="{{ route('export', array_merge(['dataset' => $dataset, 'format' => $fmt], array_filter($params, fn ($v) => $v !== null && $v !== ''))) }}"><x-icon :name="$icon" size="16" />{{ $fmtName }}</a>
    @endforeach
    @if ($round ?? false)
      <span class="dd-sep"></span>
      <a role="menuitem" href="{{ route('settings') }}#export"><x-icon name="settings" size="16" />More options</a>
    @endif
  </div>
</div>
