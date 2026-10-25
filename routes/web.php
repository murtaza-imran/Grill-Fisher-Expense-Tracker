<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('sales', SaleController::class)->except('show');
    Route::resource('expenses', ExpenseController::class)->except('show');


    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/export', [ExportController::class, 'fromForm'])->name('export.form');
    Route::get('/export/{dataset}/{format}', ExportController::class)->whereIn('dataset', ['report', 'sales', 'expenses'])->name('export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/categories', [SettingsController::class, 'addCategory'])->name('settings.categories.store');
    Route::delete('/settings/categories/{category}', [SettingsController::class, 'deleteCategory'])->name('settings.categories.destroy');
    Route::put('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::get('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
});
