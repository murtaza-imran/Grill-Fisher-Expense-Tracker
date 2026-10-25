<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from') ?: Carbon::today()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: Carbon::today()->toDateString();
        $q = trim((string) $request->query('q'));
        $query = Sale::with('items')->whereBetween('date', [$from, $to]);
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('customer', 'like', "%$q%")->orWhere('note', 'like', "%$q%")
                ->orWhereHas('items', fn ($i) => $i->where('name', 'like', "%$q%")));
        }
        $totals = (clone $query)->toBase()->reorder()->selectRaw("COUNT(*) n, COALESCE(SUM(total), 0) t, COALESCE(SUM(CASE WHEN payment = 'cash' THEN total END), 0) cash, COALESCE(SUM(CASE WHEN payment = 'online' THEN total END), 0) online")->first();
        $sales = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();
        return view('sales.index', compact('sales', 'totals', 'from', 'to', 'q'));
    }

    public function create()
    {
        return $this->form(new Sale(['date' => Carbon::today(), 'payment' => 'cash']));
    }

    public function edit(Sale $sale)
    {
        $sale->load('items');
        return $this->form($sale);
    }

    private function form(Sale $sale)
    {
        return view(request()->ajax() ? 'sales._form' : 'sales.form', [
            'modal' => request()->ajax(),
            'sale' => $sale,
        ]);
    }

    public function store(Request $request)
    {
        $sale = DB::transaction(fn () => $this->save(new Sale(), $request));
        $sale->load('items');
        session()->flash('saved', [
            'title' => 'Sale saved',
            'amount' => 'Rs. ' . number_format($sale->total),
            'text' => $sale->summary() . ' · ' . ucfirst($sale->payment),
            'ok' => 'Add another sale',
            'again' => route('sales.create'),
            'alt' => ['label' => 'View sales', 'href' => route('sales.index')],
        ]);
        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect()->route('sales.create');
    }

    public function update(Request $request, Sale $sale)
    {
        DB::transaction(fn () => $this->save($sale, $request));
        $sale->load('items');
        session()->flash('saved', [
            'title' => 'Sale updated',
            'amount' => 'Rs. ' . number_format($sale->total),
            'text' => $sale->summary() . ' · ' . ucfirst($sale->payment),
            'ok' => 'Done',
        ]);
        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect()->route('sales.index');
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        // go back to where the delete started, unless that was this sale's own edit page
        $back = url()->previous();
        $to = str_contains($back, '/sales/' . $sale->id) ? route('sales.index') : $back;
        return redirect($to)->with('ok', 'Sale deleted');
    }

    private function save(Sale $sale, Request $request): Sale
    {
        $data = $request->validate([
            'category' => 'required|in:' . implode(',', Sale::CATEGORIES),
            'amount' => 'required|integer|min:1|max:100000000',
            'payment' => 'required|in:cash,online',
            'date' => 'required|date',
        ], [
            'category.required' => 'Choose BBQ, Fish or Other.',
            'amount.required' => 'Enter the sale amount.',
            'amount.min' => 'Enter the sale amount.',
        ]);

        $sale->fill(['date' => $data['date'], 'payment' => $data['payment'], 'total' => $data['amount'], 'customer' => null, 'note' => null])->save();
        $sale->items()->delete();
        $sale->items()->create(['name' => $data['category'], 'qty' => 1, 'price' => $data['amount']]);
        return $sale;
    }
}
