<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Support\Exporter;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Downloads for Reports, Sales and Expenses in CSV, Excel, PDF, Word or JSON — using the same filters as the page. */
class ExportController extends Controller
{
    public function __invoke(Request $request, string $dataset, string $format)
    {
        abort_unless(in_array($format, Exporter::FORMATS, true), 404);
        $export = match ($dataset) {
            'report' => $this->report($request),
            'sales' => $this->sales($request),
            'expenses' => $this->expenses($request),
            default => abort(404),
        };
        return $export->download($format);
    }

    /** Settings form: ?dataset=…&format=…&from=…&to=… */
    public function fromForm(Request $request)
    {
        $data = $request->validate([
            'dataset' => 'required|in:report,sales,expenses',
            'format' => 'required|in:' . implode(',', Exporter::FORMATS),
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], ['to.after_or_equal' => 'The "To" date must be on or after the "From" date.']);
        return redirect()->route('export', ['dataset' => $data['dataset'], 'format' => $data['format'], 'from' => $data['from'], 'to' => $data['to']]);
    }

    private function dates(Request $request): array
    {
        $t = Carbon::today();
        $from = $request->query('from') ?: $t->copy()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: $t->toDateString();
        return [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()];
    }

    private function period(string $from, string $to): string
    {
        $a = Carbon::parse($from); $b = Carbon::parse($to);
        return $from === $to ? $a->format('j F Y') : $a->format('j M Y') . ' – ' . $b->format('j M Y');
    }

    private function rs(int $n): string
    {
        return ($n < 0 ? '-' : '') . 'Rs. ' . number_format(abs($n));
    }

    private function report(Request $request): Exporter
    {
        $reports = app(ReportController::class);
        [$from, $to] = $reports->range($request);
        $rows = $reports->rows($from, $to);
        $sales = (int) $rows->where('amount', '>', 0)->sum('amount');
        $exp = (int) -$rows->where('amount', '<', 0)->sum('amount');
        return new Exporter(
            'Business report', $this->period($from, $to),
            ['Date', 'Type', 'Detail', 'Customer / paid to', 'Payment', 'Amount (Rs.)'],
            $rows->map(fn ($r) => [Carbon::parse($r['date'])->format('d M Y'), $r['type'], $r['detail'], $r['party'] ?? '', $r['pay'], (int) $r['amount']])->all(),
            [5],
            ['Sales' => $this->rs($sales), 'Expenses' => $this->rs($exp), 'Profit' => $this->rs($sales - $exp), 'Entries' => (string) $rows->count()],
            "grill-fisher-report_{$from}_to_{$to}",
        );
    }

    private function sales(Request $request): Exporter
    {
        [$from, $to] = $this->dates($request);
        $q = trim((string) $request->query('q'));
        $query = Sale::with('items')->whereBetween('date', [$from, $to]);
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('customer', 'like', "%$q%")->orWhere('note', 'like', "%$q%")
                ->orWhereHas('items', fn ($i) => $i->where('name', 'like', "%$q%")));
        }
        $sales = $query->orderByDesc('date')->orderByDesc('id')->get();
        return new Exporter(
            'Sales', $this->period($from, $to) . ($q !== '' ? " · search: \"$q\"" : ''),
            ['Date', 'Items', 'Customer', 'Payment', 'Note', 'Amount (Rs.)'],
            $sales->map(fn ($s) => [$s->date->format('d M Y'), $s->summary(), $s->customer ?? '', ucfirst($s->payment), $s->note ?? '', (int) $s->total])->all(),
            [5],
            [
                'Total sales' => $this->rs((int) $sales->sum('total')), 'Orders' => (string) $sales->count(),
                'Cash' => $this->rs((int) $sales->where('payment', 'cash')->sum('total')), 'Online' => $this->rs((int) $sales->where('payment', 'online')->sum('total')),
            ],
            "grill-fisher-sales_{$from}_to_{$to}",
        );
    }

    private function expenses(Request $request): Exporter
    {
        [$from, $to] = $this->dates($request);
        $cat = (string) $request->query('category');
        $query = Expense::whereBetween('date', [$from, $to]);
        if ($cat !== '') {
            $query->where('category', $cat);
        }
        $list = $query->orderByDesc('date')->orderByDesc('id')->get();
        return new Exporter(
            'Expenses', $this->period($from, $to) . ($cat !== '' ? " · $cat" : ''),
            ['Date', 'Category', 'Paid to', 'Note', 'Payment', 'Amount (Rs.)'],
            $list->map(fn ($x) => [$x->date->format('d M Y'), $x->category, $x->vendor ?? '', $x->note ?? '', ucfirst($x->payment), (int) $x->amount])->all(),
            [5],
            [
                'Total expenses' => $this->rs((int) $list->sum('amount')), 'Entries' => (string) $list->count(),
                'Paid cash' => $this->rs((int) $list->where('payment', 'cash')->sum('amount')), 'Paid online' => $this->rs((int) $list->where('payment', 'online')->sum('amount')),
            ],
            "grill-fisher-expenses_{$from}_to_{$to}",
        );
    }
}
