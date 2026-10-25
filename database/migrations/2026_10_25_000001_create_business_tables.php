<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('price');
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });

        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->date('date')->index();
            $t->string('customer')->nullable();
            $t->enum('payment', ['cash', 'online'])->default('cash');
            $t->unsignedInteger('total')->default(0);
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('qty');
            $t->unsignedInteger('price');
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->date('date')->index();
            $t->string('category');
            $t->unsignedInteger('amount');
            $t->string('vendor')->nullable();
            $t->enum('payment', ['cash', 'online'])->default('cash');
            $t->string('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expenses', 'sale_items', 'sales', 'expense_categories', 'menu_items'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
