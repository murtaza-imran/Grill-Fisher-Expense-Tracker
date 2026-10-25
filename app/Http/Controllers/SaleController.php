<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|integer|min:1|max:100000000',
            'payment' => 'required|in:cash,online',
            'date' => 'required|date',
        ], ['amounts.*.min' => 'Enter an amount above 0.', 'amounts.*.integer' => 'Enter the amount in rupees.']);

        // BBQ / Fish / Other — any of them, each with its own amount
        $amounts = collect($data['amounts'] ?? [])->only(Sale::CATEGORIES)->filter(fn ($v) => (int) $v > 0)->map(fn ($v) => (int) $v);
        if ($amounts->isEmpty()) {
            throw ValidationException::withMessages(['amounts' => 'Choose BBQ, Fish or Other and enter the amount.']);
        }

        $sale->fill(['date' => $data['date'], 'payment' => $data['payment'], 'total' => $amounts->sum(), 'customer' => null, 'note' => null])->save();
        $sale->items()->delete();
        foreach (Sale::CATEGORIES as $cat) {
            if ($amounts->has($cat)) {
                $sale->items()->create(['name' => $cat, 'qty' => 1, 'price' => $amounts[$cat]]);
            }
        }
        return $sale;
    }
}
