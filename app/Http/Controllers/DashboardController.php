<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Percent change, or null when there is nothing to compare against. */
    private function delta(int $now, int $before): ?int
    {
        return $before > 0 ? (int) round(($now - $before) / $before * 100) : null;
    }

    public function index(Request $request)
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $monthStart = $today->copy()->startOfMonth();
        $lastMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $lastMonthSameDay = $lastMonthStart->copy()->addDays(min($today->day, $lastMonthStart->daysInMonth) - 1);
        $days = in_array((int) $request->query('days'), [7, 14, 30]) ? (int) $request->query('days') : 14;

        $ymd = fn (Carbon $a, Carbon $b) => [$a->toDateString(), $b->toDateString()];
        $sumSales = fn ($a, $b) => (int) Sale::whereBetween('date', $ymd($a, $b))->sum('total');
        $sumExp = fn ($a, $b) => (int) Expense::whereBetween('date', $ymd($a, $b))->sum('amount');

        $salesToday = Sale::where('date', $today->toDateString())->get();
        $expToday = Expense::where('date', $today->toDateString())->get();
        $salesYest = $sumSales($yesterday, $yesterday);
        $expYest = $sumExp($yesterday, $yesterday);
        $monthSales = $sumSales($monthStart, $today);
        $monthExp = $sumExp($monthStart, $today);
        $lastMonthProfit = $sumSales($lastMonthStart, $lastMonthSameDay) - $sumExp($lastMonthStart, $lastMonthSameDay);

        // daily series for the chart, plus the same-length period before it
        $start = $today->copy()->subDays($days - 1);
        $byDay = fn ($rows) => $rows->mapWithKeys(fn ($r) => [substr((string) $r->d, 0, 10) => (int) $r->t]);
        $s = $byDay(Sale::whereBetween('date', $ymd($start, $today))->selectRaw('date d, SUM(total) t')->groupBy('date')->get());
        $e = $byDay(Expense::whereBetween('date', $ymd($start, $today))->selectRaw('date d, SUM(amount) t')->groupBy('date')->get());
        $daily = [];
        for ($d = $start->copy(); $d->lte($today); $d->addDay()) {
            $k = $d->toDateString();
            $daily[] = ['d' => $k, 's' => $s[$k] ?? 0, 'e' => $e[$k] ?? 0];
        }
        $periodSales = array_sum(array_column($daily, 's'));
        $prevSales = $sumSales($start->copy()->subDays($days), $start->copy()->subDay());

        $cats = Expense::whereBetween('date', $ymd($monthStart, $today))->selectRaw('category k, SUM(amount) v')->groupBy('category')->orderByDesc('v')->get()
            ->map(fn ($r) => ['k' => $r->k, 'v' => (int) $r->v])->values();

        $best = SaleItem::whereHas('sale', fn ($q) => $q->whereBetween('date', $ymd($monthStart, $today)))
            ->selectRaw('name, SUM(qty) q, SUM(qty * price) v')->groupBy('name')->orderByDesc('q')->limit(5)->get();

        // busy hours: orders in the last 30 days by weekday × time slot (time the sale was entered)
        $slots = ['Before 5 PM', '5 – 8 PM', '8 – 11 PM', '11 PM – late'];
        $slotOf = fn (int $h) => $h >= 6 && $h < 17 ? 0 : ($h >= 17 && $h < 20 ? 1 : ($h >= 20 && $h < 23 ? 2 : 3));
        $heat = array_fill(0, 4, array_fill(0, 7, 0));
        $recentSales = Sale::where('date', '>=', $today->copy()->subDays(29)->toDateString())->get(['date', 'created_at']);
        foreach ($recentSales as $x) {
            $heat[$slotOf((int) $x->created_at->format('G'))][$x->date->dayOfWeek]++;
        }
        $orders30 = $recentSales->count();
        $ordersPrev30 = Sale::whereBetween('date', $ymd($today->copy()->subDays(59), $today->copy()->subDays(30)))->count();

        $recent = collect()
            ->merge(Sale::with('items')->orderByDesc('date')->orderByDesc('id')->limit(10)->get()->map(fn ($x) => [
                't' => 's', 'date' => $x->date, 'at' => $x->created_at, 'key' => $x->date->format('Ymd') . $x->created_at?->format('His'),
                'title' => $x->summary() ?: 'Sale', 'status' => ucfirst($x->payment), 'amt' => $x->total,
                'url' => route('sales.edit', $x)]))
            ->merge(Expense::orderByDesc('date')->orderByDesc('id')->limit(10)->get()->map(fn ($x) => [
                't' => 'e', 'date' => $x->date, 'at' => $x->created_at, 'key' => $x->date->format('Ymd') . $x->created_at?->format('His'),
                'title' => $x->category, 'status' => 'Expense', 'amt' => -$x->amount,
                'url' => route('expenses.edit', $x)]))
            ->sortByDesc('key')->take(10)->values();

        $mix = Sale::whereBetween('date', $ymd($monthStart, $today))->toBase()
            ->selectRaw("COUNT(*) n, COALESCE(SUM(CASE WHEN payment = 'cash' THEN total END), 0) cash, COALESCE(SUM(CASE WHEN payment = 'online' THEN total END), 0) online")->first();
        $itemsSold = (int) SaleItem::whereHas('sale', fn ($q) => $q->whereBetween('date', $ymd($monthStart, $today)))->sum('qty');

        $salesTodayTotal = (int) $salesToday->sum('total');
        $expTodayTotal = (int) $expToday->sum('amount');

        return view('dashboard', [
            'salesToday' => $salesTodayTotal, 'salesTodayN' => $salesToday->count(),
            'expToday' => $expTodayTotal, 'expTodayN' => $expToday->count(),
            'dSales' => $this->delta($salesTodayTotal, $salesYest),
            'dExp' => $this->delta($expTodayTotal, $expYest),
            'dProfit' => $this->delta($salesTodayTotal - $expTodayTotal, $salesYest - $expYest),
            'monthSales' => $monthSales, 'monthExp' => $monthExp,
            'dMonth' => $this->delta($monthSales - $monthExp, $lastMonthProfit),
            'daily' => $daily, 'days' => $days, 'periodSales' => $periodSales, 'dPeriod' => $this->delta($periodSales, $prevSales),
            'cats' => $cats, 'best' => $best, 'recent' => $recent,
            'mixCash' => (int) $mix->cash, 'mixOnline' => (int) $mix->online, 'monthOrders' => (int) $mix->n, 'itemsSold' => $itemsSold,
            'heat' => $heat, 'slots' => $slots, 'orders30' => $orders30, 'dOrders' => $this->delta($orders30, $ordersPrev30),
        ]);
    }
}
