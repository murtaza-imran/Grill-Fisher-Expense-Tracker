<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportController extends Controller
{
    public function range(Request $request): array
    {
        $t = Carbon::today();
        $q = $request->query('q', $request->has('from') ? '' : 'month');
        [$from, $to] = match ($q) {
            'today' => [$t, $t],
            'week' => [$t->copy()->subDays(6), $t],
            'month' => [$t->copy()->startOfMonth(), $t],
            'last' => [$t->copy()->subMonthNoOverflow()->startOfMonth(), $t->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [Carbon::parse($request->query('from') ?: $t->copy()->startOfMonth()), Carbon::parse($request->query('to') ?: $t)],
        };
        return [$from->toDateString(), $to->toDateString(), $q];
    }

    public function rows(string $from, string $to)
    {
        return collect()
            ->merge(Sale::with('items')->whereBetween('date', [$from, $to])->get()->map(fn ($s) => [
                'date' => $s->date->toDateString(), 'key' => $s->date->format('Ymd') . str_pad($s->id, 9, '0', STR_PAD_LEFT),
                'type' => 'Sale', 'detail' => $s->summary(), 'party' => $s->customer, 'pay' => ucfirst($s->payment), 'amount' => $s->total,
                'edit' => route('sales.edit', $s), 'del' => route('sales.destroy', $s)]))
            ->merge(Expense::whereBetween('date', [$from, $to])->get()->map(fn ($e) => [
                'date' => $e->date->toDateString(), 'key' => $e->date->format('Ymd') . str_pad($e->id, 9, '0', STR_PAD_LEFT),
                'type' => 'Expense', 'detail' => $e->category . ($e->note ? ' — ' . $e->note : ''), 'party' => $e->vendor, 'pay' => ucfirst($e->payment), 'amount' => -$e->amount,
                'edit' => route('expenses.edit', $e), 'del' => route('expenses.destroy', $e)]))
            ->sortByDesc('key')->values();
    }

    private function paged($rows, Request $request, int $per = 20): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        return (new LengthAwarePaginator($rows->forPage($page, $per)->values(), $rows->count(), $per, $page, ['path' => $request->url()]))->withQueryString();
    }

    public function index(Request $request)
    {
        [$from, $to, $q] = $this->range($request);
        $sales = (int) Sale::whereBetween('date', [$from, $to])->sum('total');
        $salesN = Sale::whereBetween('date', [$from, $to])->count();
        $exp = (int) Expense::whereBetween('date', [$from, $to])->sum('amount');
        $expN = Expense::whereBetween('date', [$from, $to])->count();
        $days = (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $cats = Expense::whereBetween('date', [$from, $to])->selectRaw('category k, SUM(amount) v')->groupBy('category')->get()
            ->map(fn ($r) => ['k' => $r->k, 'v' => (int) $r->v])->values();
        $pays = Sale::whereBetween('date', [$from, $to])->selectRaw('payment k, SUM(total) v')->groupBy('payment')->get()
            ->map(fn ($r) => ['k' => ucfirst($r->k), 'v' => (int) $r->v])->values();
        return view('reports.index', [
            'from' => $from, 'to' => $to, 'q' => $q, 'sales' => $sales, 'salesN' => $salesN, 'exp' => $exp, 'expN' => $expN,
            'days' => $days, 'cats' => $cats, 'pays' => $pays, 'rows' => $this->paged($this->rows($from, $to), $request),
        ]);
    }

}
