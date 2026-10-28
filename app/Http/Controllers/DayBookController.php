<?php

namespace App\Http\Controllers;

use App\Models\DayClosing;
use App\Models\Expense;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The paper book, on the phone.
 *  Day cash     = that day's sale − that day's DAILY expenses
 *  Real profit  = all day cash in the month − the month's MONTHLY (fixed) expenses
 *  Daily target = monthly fixed expenses ÷ days in the month
 */
class DayBookController extends Controller
{
    private function pickDate(?string $value): Carbon
    {
        try {
            $d = $value ? Carbon::parse($value)->startOfDay() : Carbon::today();
        } catch (\Throwable) {
            $d = Carbon::today();
        }
        return $d->greaterThan(Carbon::today()) ? Carbon::today() : $d;
    }

    /** Fixed (monthly) costs of the date's month, and what that means per day. */
    private function target(Carbon $d): array
    {
        $start = $d->copy()->startOfMonth()->toDateString();
        $end = $d->copy()->endOfMonth()->toDateString();
        $fixed = (int) Expense::where('kind', 'monthly')->whereBetween('date', [$start, $end])->sum('amount');
        return ['fixed' => $fixed, 'perDay' => (int) round($fixed / $d->daysInMonth)];
    }

    public function today(Request $request)
    {
        $d = $this->pickDate($request->query('date'));
        $day = $d->toDateString();

        $sales = Sale::with('items')->where('date', $day)->orderBy('id')->get();
        $daily = Expense::where('date', $day)->where('kind', 'daily')->orderBy('id')->get();
        $monthlyToday = Expense::where('date', $day)->where('kind', 'monthly')->orderBy('id')->get();

        $byCat = [];
        foreach ($sales as $s) {
            foreach ($s->categoryAmounts() as $cat => $amt) {
                $byCat[$cat] = ($byCat[$cat] ?? 0) + $amt;
            }
        }
        $sale = (int) $sales->sum('total');
        $dailyExp = (int) $daily->sum('amount');
        $cash = $sale - $dailyExp;
        $target = $this->target($d);
        $closing = DayClosing::where('date', $day)->first();

        return view('daybook.today', [
            'd' => $d, 'isToday' => $d->isToday(),
            'sales' => $sales, 'daily' => $daily, 'monthlyToday' => $monthlyToday,
            'sale' => $sale, 'cashSale' => (int) $sales->where('payment', 'cash')->sum('total'),
            'onlineSale' => (int) $sales->where('payment', 'online')->sum('total'),
            'byCat' => $byCat, 'dailyExp' => $dailyExp, 'cash' => $cash,
            'target' => $target, 'closing' => $closing,
            'whatsapp' => $this->summary($d, $sale, (int) $sales->where('payment', 'cash')->sum('total'), (int) $sales->where('payment', 'online')->sum('total'), $daily, $cash, $target['perDay']),
        ]);
    }

    private function summary(Carbon $d, int $sale, int $cashSale, int $online, $daily, int $cash, int $perDay): string
    {
        $rs = fn ($n) => 'Rs. ' . number_format($n);
        $lines = [
            '*Grill Fisher & BBQ* — ' . $d->format('D, j M Y'),
            'Sale: ' . $rs($sale) . " (Cash {$rs($cashSale)} · Online {$rs($online)})",
            'Daily expenses: ' . $rs((int) $daily->sum('amount')),
        ];
        foreach ($daily as $e) {
            $lines[] = '  • ' . $e->category . ': ' . $rs($e->amount);
        }
        $lines[] = '*Day cash: ' . $rs($cash) . '*';
        if ($perDay > 0) {
            $diff = $cash - $perDay;
            $lines[] = 'Daily target: ' . $rs($perDay) . ' → ' . ($diff >= 0 ? '+' : '−') . $rs(abs($diff)) . ($diff >= 0 ? ' ✅' : ' ⚠️');
        }
        return implode("\n", $lines);
    }

    public function close(Request $request)
    {
        $d = $this->pickDate($request->input('date'));
        DayClosing::updateOrCreate(['date' => $d->toDateString()], ['closed_at' => now()]);

        $sale = (int) Sale::where('date', $d->toDateString())->sum('total');
        $daily = Expense::where('date', $d->toDateString())->where('kind', 'daily')->get();
        $cash = $sale - (int) $daily->sum('amount');
        $perDay = $this->target($d)['perDay'];
        $sales = Sale::where('date', $d->toDateString())->get();
        $text = $this->summary($d, $sale, (int) $sales->where('payment', 'cash')->sum('total'), (int) $sales->where('payment', 'online')->sum('total'), $daily, $cash, $perDay);

        session()->flash('saved', [
            'title' => 'Day closed — ' . $d->format('D, j M'),
            'amount' => 'Rs. ' . number_format($cash),
            'text' => 'Day cash = sale Rs. ' . number_format($sale) . ' − daily expenses Rs. ' . number_format((int) $daily->sum('amount')),
            'ok' => 'Done',
            'alt' => ['label' => 'Send on WhatsApp', 'href' => 'https://wa.me/?text=' . rawurlencode($text)],
        ]);
        return redirect()->route('daybook', ['date' => $d->toDateString()]);
    }

    public function reopen(Request $request)
    {
        $d = $this->pickDate($request->input('date'));
        DayClosing::where('date', $d->toDateString())->delete();
        return redirect()->route('daybook', ['date' => $d->toDateString()])->with('ok', 'Day opened again — you can change it.');
    }

    public function month(Request $request)
    {
        try {
            $m = $request->query('m') ? Carbon::createFromFormat('Y-m', $request->query('m'))->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Throwable) {
            $m = Carbon::today()->startOfMonth();
        }
        if ($m->greaterThan(Carbon::today()->startOfMonth())) {
            $m = Carbon::today()->startOfMonth();
        }
        $start = $m->toDateString();
        $end = $m->copy()->endOfMonth()->toDateString();
        $last = $m->isSameMonth(Carbon::today()) ? Carbon::today() : $m->copy()->endOfMonth();

        $saleByDay = Sale::whereBetween('date', [$start, $end])->selectRaw('date d, SUM(total) t')->groupBy('date')->pluck('t', 'd');
        $expByDay = Expense::where('kind', 'daily')->whereBetween('date', [$start, $end])->selectRaw('date d, SUM(amount) t')->groupBy('date')->pluck('t', 'd');
        $closed = DayClosing::whereBetween('date', [$start, $end])->pluck('date')->map(fn ($x) => substr((string) $x, 0, 10))->flip();
        $target = $this->target($m);

        $days = [];
        for ($d = $last->copy(); $d->gte($m); $d->subDay()) {
            $k = $d->toDateString();
            $s = (int) ($saleByDay[$k] ?? 0);
            $e = (int) ($expByDay[$k] ?? 0);
            $days[] = ['d' => $d->copy(), 'sale' => $s, 'exp' => $e, 'cash' => $s - $e, 'closed' => isset($closed[$k])];
        }
        $totSale = array_sum(array_column($days, 'sale'));
        $totExp = array_sum(array_column($days, 'exp'));
        $fixed = Expense::where('kind', 'monthly')->whereBetween('date', [$start, $end])->orderBy('date')->get();
        $fixedByCat = $fixed->groupBy('category')->map(fn ($g) => (int) $g->sum('amount'))->sortDesc();

        return view('daybook.month', [
            'm' => $m, 'days' => $days, 'totSale' => $totSale, 'totExp' => $totExp, 'totCash' => $totSale - $totExp,
            'fixed' => $fixed, 'fixedByCat' => $fixedByCat, 'fixedTotal' => (int) $fixed->sum('amount'),
            'perDay' => $target['perDay'], 'daysWithSales' => count(array_filter($days, fn ($x) => $x['sale'] > 0)),
            'hitTarget' => count(array_filter($days, fn ($x) => $target['perDay'] > 0 && $x['sale'] > 0 && $x['cash'] >= $target['perDay'])),
            'prev' => $m->copy()->subMonth()->format('Y-m'),
            'next' => $m->isSameMonth(Carbon::today()) ? null : $m->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
