<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Daily expenses come off that day's sale; monthly (fixed) expenses come off the month's total.
return new class extends Migration
{
    private const MONTHLY = ['Rent', 'Staff salary', 'Electricity & bills'];

    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $t) {
            $t->string('kind', 10)->default('daily')->after('category');
        });
        Schema::table('expense_categories', function (Blueprint $t) {
            $t->string('kind', 10)->default('daily')->after('name');
        });
        DB::table('expense_categories')->whereIn('name', self::MONTHLY)->update(['kind' => 'monthly']);
        DB::table('expenses')->whereIn('category', self::MONTHLY)->update(['kind' => 'monthly']);

        // a day you have finished and "closed" (like signing the paper page)
        Schema::create('day_closings', function (Blueprint $t) {
            $t->id();
            $t->date('date')->unique();
            $t->timestamp('closed_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closings');
        Schema::table('expense_categories', fn (Blueprint $t) => $t->dropColumn('kind'));
        Schema::table('expenses', fn (Blueprint $t) => $t->dropColumn('kind'));
    }
};
