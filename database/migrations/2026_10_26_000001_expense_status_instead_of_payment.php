<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Expenses track whether they are paid or still pending, instead of cash/online.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $t) {
            $t->string('status', 10)->default('paid')->after('vendor');
        });
        DB::table('expenses')->update(['status' => 'paid']);
        Schema::table('expenses', function (Blueprint $t) {
            $t->dropColumn('payment');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $t) {
            $t->string('payment', 10)->default('cash')->after('vendor');
        });
        Schema::table('expenses', function (Blueprint $t) {
            $t->dropColumn('status');
        });
    }
};
