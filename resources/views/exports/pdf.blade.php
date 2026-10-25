<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 26px 30px 34px; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1d1912; }
  .brand { color: #c98500; font-weight: bold; font-size: 10px; letter-spacing: .5px; text-transform: uppercase; }
  h1 { font-size: 20px; margin: 2px 0 2px; }
  .period { color: #7a7064; font-size: 9.5px; margin-bottom: 14px; }
  .sum { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 14px; }
  .sum td { background: #f7f2e8; border: 1px solid #ece2cf; border-radius: 6px; padding: 8px 10px; }
  .sum small { display: block; color: #7a7064; font-size: 8.5px; }
  .sum b { font-size: 13px; }
  table.data { width: 100%; border-collapse: collapse; }
  .data th { background: #e8a91a; color: #1a1105; text-align: left; font-size: 9px; padding: 6px 7px; }
  .data td { padding: 5px 7px; border-bottom: 1px solid #ece6db; vertical-align: top; }
  .data tr:nth-child(even) td { background: #fbf8f2; }
  .r, .data th.r { text-align: right; white-space: nowrap; }
  .neg { color: #c2392c; }
  .empty { text-align: center; color: #7a7064; padding: 24px; }
  .foot { position: fixed; bottom: -20px; left: 0; right: 0; color: #9a9184; font-size: 8px; }
</style>
</head>
<body>
  <div class="foot">Grill Fisher &amp; BBQ · Ameer Road, Bilal Gunj, Lahore · Generated {{ now()->format('j M Y, g:i A') }}</div>
  <div class="brand">Grill Fisher &amp; BBQ</div>
  <h1>{{ $title }}</h1>
  <div class="period">{{ $period }}</div>

  @if ($summary)
    <table class="sum"><tr>
      @foreach ($summary as $k => $v)<td><small>{{ $k }}</small><b>{{ $v }}</b></td>@endforeach
    </tr></table>
  @endif

  <table class="data">
    <thead><tr>@foreach ($headers as $i => $h)<th class="{{ in_array($i, $moneyCols, true) ? 'r' : '' }}">{{ $h }}</th>@endforeach</tr></thead>
    <tbody>
      @forelse ($rows as $row)
        <tr>
          @foreach (array_values($row) as $i => $v)
            @if (in_array($i, $moneyCols, true))
              <td class="r {{ (int) $v < 0 ? 'neg' : '' }}">{{ $money($v) }}</td>
            @else
              <td>{{ $v }}</td>
            @endif
          @endforeach
        </tr>
      @empty
        <tr><td colspan="{{ count($headers) }}" class="empty">No entries in this period.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
