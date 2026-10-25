<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from') ?: Carbon::today()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: Carbon::today()->toDateString();
        $cat = (string) $request->query('category');
        $query = Expense::whereBetween('date', [$from, $to]);
        if ($cat !== '') {
            $query->where('category', $cat);
        }
        $totals = (clone $query)->toBase()->selectRaw("COUNT(*) n, COALESCE(SUM(amount), 0) t, COALESCE(SUM(CASE WHEN payment = 'cash' THEN amount END), 0) cash, COALESCE(SUM(CASE WHEN payment = 'online' THEN amount END), 0) online")->first();
        $topCat = (clone $query)->selectRaw('category, SUM(amount) v')->groupBy('category')->orderByDesc('v')->value('category');
        $expenses = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->pluck('name');
        return view('expenses.index', compact('expenses', 'totals', 'topCat', 'from', 'to', 'cat', 'categories'));
    }

    public function create()
    {
        return $this->form(new Expense(['date' => Carbon::today(), 'payment' => 'cash']));
    }

    public function edit(Expense $expense)
    {
        return $this->form($expense);
    }

    private function form(Expense $expense)
    {
        return view(request()->ajax() ? 'expenses._form' : 'expenses.form', [
            'modal' => request()->ajax(),
            'expense' => $expense,
            'categories' => ExpenseCategory::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $e = DB::transaction(fn () => $this->save(new Expense(), $request));
        session()->flash('saved', [
            'title' => 'Expense saved',
            'amount' => 'Rs. ' . number_format($e->amount),
            'text' => $e->category . ($e->vendor ? ' · ' . $e->vendor : '') . ' · ' . ucfirst($e->payment),
            'ok' => 'Add another expense',
            'again' => route('expenses.create'),
            'alt' => ['label' => 'View expenses', 'href' => route('expenses.index')],
        ]);
        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect()->route('expenses.create');
    }

    public function update(Request $request, Expense $expense)
    {
        DB::transaction(fn () => $this->save($expense, $request));
        session()->flash('saved', [
            'title' => 'Expense updated',
            'amount' => 'Rs. ' . number_format($expense->amount),
            'text' => $expense->category . ($expense->vendor ? ' · ' . $expense->vendor : '') . ' · ' . ucfirst($expense->payment),
            'ok' => 'Done',
        ]);
        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect()->route('expenses.index');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        // go back to where the delete started, unless that was this expense's own edit page
        $back = url()->previous();
        $to = str_contains($back, '/expenses/' . $expense->id) ? route('expenses.index') : $back;
        return redirect($to)->with('ok', 'Expense deleted');
    }

    private function save(Expense $expense, Request $request): Expense
    {
        $data = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|integer|min:1|max:100000000',
            'category' => 'required|string|max:80',
            'vendor' => 'nullable|string|max:120',
            'payment' => 'required|in:cash,online',
            'note' => 'nullable|string|max:255',
        ]);

        $expense->fill($data)->save();
        return $expense;
    }
}
