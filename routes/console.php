<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// php artisan data:reset   — delete every sale and expense (login and expense categories are kept)
Artisan::command('data:reset {--force : Do not ask for confirmation}', function () {
    $sales = DB::table('sales')->count();
    $expenses = DB::table('expenses')->count();
    if (! $this->option('force') && ! $this->confirm("Delete ALL {$sales} sales and {$expenses} expenses? This cannot be undone.")) {
        $this->info('Nothing was deleted.');
        return 1;
    }
    DB::transaction(function () {
        DB::table('sale_items')->delete();
        DB::table('sales')->delete();
        DB::table('expenses')->delete();
        DB::table('day_closings')->delete();
    });
    $this->info("Deleted {$sales} sales and {$expenses} expenses. Your login and expense categories are kept.");
    return 0;
})->purpose('Delete all sales and expenses (keeps login and categories)');
