{{-- Pagination bar: $p = LengthAwarePaginator, $what = noun for the count --}}
@if ($p->total() > 0)
  @php
    $cur = $p->currentPage(); $last = $p->lastPage();
    $nums = collect([1, $cur - 1, $cur, $cur + 1, $last])->filter(fn ($n) => $n >= 1 && $n <= $last)->unique()->sort()->values();
  @endphp
  <nav class="pager" aria-label="Pages">
    <span class="info">Showing <b>{{ number_format($p->firstItem()) }}–{{ number_format($p->lastItem()) }}</b> of <b>{{ number_format($p->total()) }}</b> {{ $what }}</span>
    @if ($last > 1)
      <div class="pages">
        @if ($p->onFirstPage())
          <span class="off" aria-hidden="true"><x-icon name="arrow-left" size="15" /></span>
        @else
          <a href="{{ $p->previousPageUrl() }}" rel="prev" aria-label="Previous page"><x-icon name="arrow-left" size="15" /></a>
        @endif
        @foreach ($nums as $i => $n)
          @if ($i > 0 && $n - $nums[$i - 1] > 1)<span class="gap num">…</span>@endif
          @if ($n === $cur)
            <span class="on" aria-current="page">{{ $n }}</span>
          @else
            <a href="{{ $p->url($n) }}" class="num">{{ $n }}</a>
          @endif
        @endforeach
        @if ($p->hasMorePages())
          <a href="{{ $p->nextPageUrl() }}" rel="next" aria-label="Next page"><x-icon name="arrow-left" size="15" style="transform: rotate(180deg)" /></a>
        @else
          <span class="off" aria-hidden="true"><x-icon name="arrow-left" size="15" style="transform: rotate(180deg)" /></span>
        @endif
      </div>
    @endif
  </nav>
@endif
