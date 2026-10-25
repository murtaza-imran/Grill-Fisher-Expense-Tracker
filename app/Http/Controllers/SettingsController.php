<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }

    public function addCategory(Request $request)
    {
        ExpenseCategory::create($request->validate(['name' => 'required|string|max:80|unique:expense_categories,name']));
        return back()->with('ok', 'Category added');
    }

    public function deleteCategory(ExpenseCategory $category)
    {
        $category->delete();
        return back()->with('ok', 'Category removed');
    }


    public function password(Request $request)
    {
        $request->validate(['current' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => Hash::make($request->input('password'))]);
        return back()->with('ok', 'Password changed');
    }

    public function backup()
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'sales' => Sale::with('items')->get(),
            'expenses' => Expense::all(),
            'expense_categories' => ExpenseCategory::all(),
        ];
        return response()->streamDownload(
            fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            'grill-fisher-backup_' . now()->toDateString() . '.json',
            ['Content-Type' => 'application/json']
        );
    }
}
